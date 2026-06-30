<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ShippingService
{
    /**
     * Break a set of cart/order items down into one row per shop, each with its
     * subtotal and shipping fee. This is the single place that knows shipping
     * is evaluated per shop (matching the per-shop order split at checkout) and
     * that soft-deleted products (null relation) are skipped.
     *
     * @return Collection<int, array{shop_id: int, shop_name: string, subtotal: float, shipping_fee: float}>
     */
    public function breakdownForItems(Collection $items): Collection
    {
        return $items
            ->filter(fn ($item) => $item->product !== null)
            ->groupBy(fn ($item) => $item->product->shop_id)
            ->map(function ($shopItems) {
                $subtotal = $shopItems->sum(fn ($item) => $item->quantity * $item->unit_price);
                $first = $shopItems->first();

                return [
                    'shop_id' => $first->product->shop_id,
                    'shop_name' => $first->product->shop->name ?? '',
                    'subtotal' => round($subtotal, 2),
                    'shipping_fee' => round($this->feeForSubtotal($subtotal), 2),
                ];
            })
            ->values();
    }

    /**
     * Calculate the shipping fee for a single shop's subtotal.
     *
     * Shipping is evaluated per shop (per order), since the cart is split
     * into one order per shop at checkout. A subtotal that reaches the
     * free-shipping threshold ships for free; otherwise the flat fee applies.
     */
    public function feeForSubtotal(float $subtotal): float
    {
        $threshold = config('shipping.free_threshold');

        if ($threshold !== null && $subtotal >= (float) $threshold) {
            return 0.0;
        }

        return round((float) config('shipping.flat_fee'), 2);
    }

    /**
     * The shipping rule as a plain array for the front-end to mirror when
     * estimating fees client-side. Shared by the cart and checkout pages so
     * the exposed shape stays in one place.
     */
    public function publicConfig(): array
    {
        $threshold = config('shipping.free_threshold');

        return [
            'flat_fee' => (float) config('shipping.flat_fee'),
            'free_threshold' => $threshold !== null ? (float) $threshold : null,
        ];
    }
}
