<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDashboardPeriod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use ResolvesDashboardPeriod;

    public function index(Request $request)
    {
        $period = $this->resolvePeriod($request);
        [$start, $end] = $this->periodRange($period);
        [$prevStart, $prevEnd] = $this->prevPeriodRange($period);

        // All-time platform totals (period-independent, shown as the header row).
        $totals = [
            'total_users' => User::count(),
            'total_shops' => Shop::count(),
            'pending_shops' => Shop::where('status', Shop::STATUS_PENDING)->count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => (float) Order::where('status', '!=', Order::STATUS_CANCELLED)->sum('total'),
        ];

        // Period-scoped revenue (paid orders), keyed on paid_at.
        $revenue = (float) Order::whereNotNull('paid_at')
            ->when($period !== 'all', fn ($q) => $q->whereBetween('paid_at', [$start, $end]))
            ->sum('total');

        $revenuePrev = null;
        $revenueGrowth = null;
        if ($period !== 'all') {
            $revenuePrev = (float) Order::whereNotNull('paid_at')
                ->whereBetween('paid_at', [$prevStart, $prevEnd])
                ->sum('total');
            $revenueGrowth = $this->periodGrowth($revenue, $revenuePrev);
        }

        // Order status distribution across the platform, keyed on created_at
        // (activity volume in the period, not payment timing — mirrors seller).
        $allStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];
        $rawCounts = Order::query()
            ->when($period !== 'all', fn ($q) => $q->whereBetween('created_at', [$start, $end]))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $orderCounts = collect($allStatuses)
            ->mapWithKeys(fn ($s) => [$s => (int) ($rawCounts[$s] ?? 0)])
            ->all();

        // Top shops by revenue in the period (platform analog of seller's top products).
        $topShops = Order::query()
            ->join('shops', 'orders.shop_id', '=', 'shops.id')
            ->whereNotNull('orders.paid_at')
            ->when($period !== 'all', fn ($q) => $q->whereBetween('orders.paid_at', [$start, $end]))
            ->selectRaw('shops.name as shop_name, COUNT(*) as orders, SUM(orders.total) as revenue')
            ->groupBy('shops.id', 'shops.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $chartData = $this->dailyRevenueSeries(Order::query(), $period, $start, $end);

        return Inertia::render('Admin/Dashboard', [
            'period' => $period,
            'stats' => array_merge($totals, [
                'revenue' => $revenue,
                'revenue_prev' => $revenuePrev,
                'revenue_growth' => $revenueGrowth,
                'order_counts' => $orderCounts,
                'period_orders' => array_sum($orderCounts),
            ]),
            'chartData' => $chartData,
            'topShops' => $topShops,
        ]);
    }
}
