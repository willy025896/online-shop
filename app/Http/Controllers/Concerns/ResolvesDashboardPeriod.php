<?php

namespace App\Http\Controllers\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared period-window logic for dashboards (seller + admin).
 *
 * Revenue is always keyed on `paid_at` (when payment cleared); callers that
 * count order *activity* should key on `created_at` instead — this trait only
 * owns the period math, not the choice of timestamp column.
 */
trait ResolvesDashboardPeriod
{
    protected const VALID_PERIODS = ['today', 'week', 'month', 'all'];

    protected function resolvePeriod(Request $request, string $default = 'month'): string
    {
        $period = $request->query('period');

        return in_array($period, self::VALID_PERIODS, true) ? $period : $default;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function periodRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::today(), Carbon::now()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [Carbon::createFromTimestamp(0), Carbon::now()],
        };
    }

    /**
     * The comparison window immediately preceding $period. 'all' has no
     * comparison period; callers must guard with ($period !== 'all').
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function prevPeriodRange(string $period): array
    {
        return match ($period) {
            'today' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            default => [null, null],
        };
    }

    /**
     * Percent change of $current vs $prev, rounded to 1dp.
     * Returns null when there is no comparable baseline (prev is null),
     * and 100.0 when growing from a zero baseline.
     */
    protected function periodGrowth(float $current, ?float $prev): ?float
    {
        if ($prev === null) {
            return null;
        }

        return $prev > 0
            ? round((($current - $prev) / $prev) * 100, 1)
            : ($current > 0 ? 100.0 : null);
    }

    /**
     * Daily revenue buckets (paid orders) over the period, with zero-filled
     * gaps so the chart x-axis is continuous. For 'all', shows the last 30 days.
     *
     * $base is a fresh Order query already scoped to the caller (e.g.
     * Order::query() for admin, Order::where('shop_id', ...) for a seller).
     *
     * @return array<int, array{date: string, revenue: float}>
     */
    protected function dailyRevenueSeries(Builder $base, string $period, Carbon $start, Carbon $end): array
    {
        if ($period === 'all') {
            $chartStart = Carbon::now()->subDays(29)->startOfDay();
            $chartEnd = Carbon::now()->endOfDay();
        } else {
            $chartStart = $start;
            $chartEnd = $end;
        }

        $rows = $base
            ->whereNotNull('paid_at')
            ->whereBetween('paid_at', [$chartStart, $chartEnd])
            // DATE(paid_at) uses the DB server timezone (UTC by default).
            // If APP_TIMEZONE differs from UTC, daily buckets shift by the UTC gap.
            // Fix: align the MySQL session timezone with APP_TIMEZONE, or use CONVERT_TZ().
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
