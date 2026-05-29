<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RecommendationService
{
    /**
     * Build the related-products list shown on a product page.
     *
     * Three signals are applied in order, de-duplicating as we go and stopping
     * once $limit products have been collected:
     *   1. Frequently bought together — products co-occurring in the same orders.
     *   2. Same category — weighted by featured flag and average rating.
     *   3. Same shop — other products from the same seller (last-resort filler).
     *
     * Only active, in-stock products are returned; the source product is never
     * included.
     */
    public function relatedTo(Product $product, int $limit = 4): Collection
    {
        $collected = collect();

        $strategies = [
            fn (array $exclude, int $remaining) => $this->boughtTogether($product, $exclude, $remaining),
            fn (array $exclude, int $remaining) => $this->sameCategory($product, $exclude, $remaining),
            fn (array $exclude, int $remaining) => $this->sameShop($product, $exclude, $remaining),
        ];

        foreach ($strategies as $strategy) {
            $remaining = $limit - $collected->count();
            if ($remaining <= 0) {
                break;
            }

            $exclude = $collected->pluck('id')->push($product->id)->all();
            $collected = $collected->concat($strategy($exclude, $remaining));
        }

        return $collected->values();
    }

    /**
     * Products that appear in the same (non-cancelled) orders as $product,
     * ranked by how many distinct orders they share with it.
     *
     * Uses a self-join on order_items to keep the co-occurrence aggregation
     * server-side and avoid a potentially unbounded PHP-side IN() list.
     */
    private function boughtTogether(Product $product, array $exclude, int $limit): Collection
    {
        // Over-fetch: some co-occurring products may be inactive/out-of-stock
        // and get filtered out during hydration.
        $rankedIds = OrderItem::query()
            ->join('order_items as pivot', 'pivot.order_id', '=', 'order_items.order_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('pivot.product_id', $product->id)
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->whereNotIn('order_items.product_id', $exclude)
            ->selectRaw('order_items.product_id, COUNT(DISTINCT order_items.order_id) as freq')
            ->groupBy('order_items.product_id')
            ->orderByDesc('freq')
            ->limit($limit * 3)
            ->pluck('product_id');

        if ($rankedIds->isEmpty()) {
            return collect();
        }

        $products = $this->baseQuery()
            ->whereIn('id', $rankedIds)
            ->get()
            ->keyBy('id');

        // Preserve the co-occurrence ranking the DB returned.
        return $rankedIds
            ->map(fn ($id) => $products->get($id))
            ->filter()
            ->take($limit)
            ->values();
    }

    /**
     * Other products in the same category, best-rated and featured first.
     */
    private function sameCategory(Product $product, array $exclude, int $limit): Collection
    {
        if ($product->category_id === null) {
            return collect();
        }

        return $this->baseQuery()
            ->where('category_id', $product->category_id)
            ->whereNotIn('id', $exclude)
            ->orderByDesc('is_featured')
            ->orderByRating()
            ->limit($limit)
            ->get();
    }

    /**
     * Other products from the same shop — last-resort filler so that brand-new
     * products (no orders, sparse category) still surface something.
     */
    private function sameShop(Product $product, array $exclude, int $limit): Collection
    {
        return $this->baseQuery()
            ->where('shop_id', $product->shop_id)
            ->whereNotIn('id', $exclude)
            ->orderByDesc('is_featured')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Base query shared by every strategy: active, in-stock, with the relations
     * ProductCard renders.
     */
    private function baseQuery(): Builder
    {
        return Product::active()
            ->where('stock', '>', 0)
            ->with(['primaryImage', 'shop:id,name,slug']);
    }
}
