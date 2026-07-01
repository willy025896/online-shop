<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\ResolvesDashboardPeriod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use ResolvesDashboardPeriod;

    public const DEFAULT_WIDGETS = [
        'revenue' => true,
        'order_status' => true,
        'top_products' => true,
        'revenue_chart' => true,
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $shop = $user->shop;
        $period = $this->resolvePeriod($request);

        [$start, $end] = $this->periodRange($period);
        [$prevStart, $prevEnd] = $this->prevPeriodRange($period);

        $revenue = $shop->orders()->whereNotNull('paid_at')
            ->when($period !== 'all', fn ($q) => $q->whereBetween('paid_at', [$start, $end]))
            ->sum('total');

        $revenuePrev = null;
        $revenueGrowth = null;
        if ($period !== 'all') {
            $revenuePrev = $shop->orders()->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$prevStart, $prevEnd])
                ->sum('total');
            $revenueGrowth = $this->periodGrowth((float) $revenue, (float) $revenuePrev);
        }

        // Status counts use created_at (when the order was placed), while revenue
        // uses paid_at (when payment cleared). This is intentional: the status grid
        // shows activity volume in the period, not payment timing.
        $allStatuses = [
            Order::STATUS_PENDING,
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];
        $rawCounts = $shop->orders()
            ->when($period !== 'all', fn ($q) => $q->whereBetween('created_at', [$start, $end]))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $orderCounts = collect($allStatuses)
            ->mapWithKeys(fn ($s) => [$s => (int) ($rawCounts[$s] ?? 0)])
            ->all();

        $topProducts = OrderItem::query()
            ->whereHas('order', fn ($q) => $q
                ->where('shop_id', $shop->id)
                ->whereNotNull('paid_at')
                ->when($period !== 'all', fn ($q) => $q->whereBetween('paid_at', [$start, $end]))
            )
            ->selectRaw('product_name, SUM(quantity) as qty, SUM(subtotal) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $chartData = $this->dailyRevenueSeries(
            Order::where('shop_id', $shop->id),
            $period,
            $start,
            $end
        );

        $stats = [
            'total_products' => $shop->products()->count(),
            'active_products' => $shop->products()->active()->count(),
            'revenue' => (float) $revenue,
            'revenue_prev' => $revenuePrev !== null ? (float) $revenuePrev : null,
            'revenue_growth' => $revenueGrowth,
            'order_counts' => $orderCounts,
            'total_orders' => array_sum($orderCounts),
        ];

        $widgets = array_merge(
            self::DEFAULT_WIDGETS,
            $user->preferences['dashboard_widgets'] ?? []
        );

        return Inertia::render('Seller/Dashboard', [
            'shop' => $shop,
            'period' => $period,
            'stats' => $stats,
            'chartData' => $chartData,
            'topProducts' => $topProducts,
            'recentOrders' => $shop->orders()->latest()->limit(5)->get(),
            'userPreferences' => $user->preferences,
            'widgets' => $widgets,
        ]);
    }
}
