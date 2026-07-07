<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payout;
use App\Models\Shop;
use App\Notifications\PayoutCompletedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for payout eligibility, money math, and generation.
 * See ADR-014.
 */
class PayoutService
{
    /**
     * Orders for $shop that are safe to pay out: completed, past the return
     * window (so their amounts can no longer change — see canRequestReturn()),
     * with no pending return request, and not already paid out.
     */
    public function eligibleOrders(Shop $shop): Collection
    {
        return $this->eligibleOrdersQuery($shop)->get();
    }

    /**
     * The commission/net breakdown for a single order, at today's config
     * rate — used both by the admin preview and by generateForShop() itself,
     * so the money math lives in exactly one place.
     */
    public function estimatedNetAmount(Order $order): float
    {
        return $this->breakdown($order)['net_amount'];
    }

    /**
     * Per-approved-shop preview of what a payout run would pay out right now
     * (display-only — creates nothing). Shops with nothing eligible are
     * omitted.
     *
     * @return BaseCollection<int, array{shop_id: int, shop_name: string, order_count: int, net_amount: float}>
     */
    public function pendingPreview(): BaseCollection
    {
        return Shop::approved()
            ->get(['id', 'name'])
            ->map(function (Shop $shop) {
                $orders = $this->eligibleOrders($shop);

                return [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'order_count' => $orders->count(),
                    'net_amount' => round($orders->sum(fn ($order) => $this->estimatedNetAmount($order)), 2),
                ];
            })
            ->filter(fn ($row) => $row['order_count'] > 0)
            ->values();
    }

    /**
     * Generates one Payout (with a PayoutItem snapshot per order) covering
     * every currently eligible, unpaid order for $shop. Returns null if there
     * is nothing to pay out (idempotent — safe to call repeatedly).
     *
     * The eligibility query (including the whereDoesntHave('payoutItem') and
     * pending-return checks) is re-run under lockForUpdate() itself, rather
     * than pre-computing an ID list and re-fetching by ID — this way, if a
     * concurrent call already claimed one of these orders and committed while
     * this call was blocked on the row lock, that order simply drops out of
     * the freshly-evaluated result set instead of causing a duplicate-insert
     * error against payout_items' unique(order_id) constraint.
     */
    public function generateForShop(Shop $shop): ?Payout
    {
        return DB::transaction(function () use ($shop) {
            $orders = $this->eligibleOrdersQuery($shop)->lockForUpdate()->get();

            if ($orders->isEmpty()) {
                return null;
            }

            $breakdowns = $orders->map(fn (Order $order) => ['order_id' => $order->id, ...$this->breakdown($order)]);

            $totals = collect(['gross_amount', 'commission_amount', 'shipping_amount', 'net_amount'])
                ->mapWithKeys(fn ($key) => [$key => round($breakdowns->sum($key), 2)])
                ->all();

            $payout = Payout::create([
                'shop_id' => $shop->id,
                ...$totals,
                'paid_at' => now(),
            ]);

            $payout->items()->createMany($breakdowns);

            $shop->loadMissing('user');
            $shop->user?->notify(new PayoutCompletedNotification($payout));

            return $payout;
        });
    }

    /**
     * Runs generateForShop() for every approved shop. Suspended/pending shops
     * are skipped — their eligible orders (if any) stay unpaid until an admin
     * handles them individually via generateForShop().
     *
     * @return Collection<int, Payout>
     */
    public function generateForAllShops(): Collection
    {
        return Shop::approved()
            ->with('user')
            ->get()
            ->map(fn (Shop $shop) => $this->generateForShop($shop))
            ->filter()
            ->values();
    }

    private function eligibleOrdersQuery(Shop $shop): Builder
    {
        return Order::select(['id', 'subtotal', 'discount', 'refunded_amount', 'shipping_fee'])
            ->where('shop_id', $shop->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->pastReturnWindow()
            ->whereDoesntHave('payoutItem')
            ->withoutPendingReturn();
    }

    /**
     * @return array{gross_amount: float, commission_amount: float, shipping_amount: float, net_amount: float}
     */
    private function breakdown(Order $order): array
    {
        $grossAmount = max(0, (float) $order->subtotal - (float) $order->discount - (float) $order->refunded_amount);
        $commissionAmount = round($grossAmount * (float) config('commission.rate'), 2);
        $shippingAmount = (float) $order->shipping_fee;

        return [
            'gross_amount' => $grossAmount,
            'commission_amount' => $commissionAmount,
            'shipping_amount' => $shippingAmount,
            'net_amount' => round($grossAmount - $commissionAmount + $shippingAmount, 2),
        ];
    }
}
