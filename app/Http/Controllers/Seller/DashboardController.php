<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const VALID_PERIODS = ['today', 'week', 'month', 'all'];

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
        $period = in_array($request->query('period'), self::VALID_PERIODS)
            ? $request->query('period')
            : 'month';

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
            $revenueGrowth = $revenuePrev > 0
                ? round((($revenue - $revenuePrev) / $revenuePrev) * 100, 1)
                : ($revenue > 0 ? 100.0 : null);
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

        $chartData = $this->buildChartData($shop, $period, $start, $end);

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

    private function periodRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::today(), Carbon::now()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [Carbon::createFromTimestamp(0), Carbon::now()],
        };
    }

    private function prevPeriodRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            default => [null, null], // 'all' has no comparison period; callers must guard with ($period !== 'all')
        };
    }

    private function buildChartData($shop, string $period, Carbon $start, Carbon $end): array
    {
        // For 'all', show last 30 days; otherwise show the period
        if ($period === 'all') {
            $chartStart = Carbon::now()->subDays(29)->startOfDay();
            $chartEnd = Carbon::now()->endOfDay();
        } else {
            $chartStart = $start;
            $chartEnd = $end;
        }

        $rows = $shop->orders()
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$chartStart, $chartEnd])
            // DATE(paid_at) uses the DB server timezone (UTC by default).
            // If APP_TIMEZONE differs from UTC, daily buckets will be offset by the UTC gap.
            // Fix: set MySQL session timezone to match APP_TIMEZONE, or use CONVERT_TZ().
            ->selectRaw('DATE(paid_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $data = [];
        $cursor = $chartStart->copy()->startOfDay();
        while ($cursor->lte($chartEnd)) {
            $dateKey = $cursor->toDateString();
            $data[] = [
                'date' => $dateKey,
                'revenue' => (float) ($rows[$dateKey]->revenue ?? 0),
            ];
            $cursor->addDay();
        }

        return $data;
    }
}
