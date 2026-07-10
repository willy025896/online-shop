<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Shared min_rating/price-range filter + sort logic for the three product
 * listing pages (home/products, shop, category) so their sort/filter
 * capabilities stay identical across the site.
 *
 * $query is intentionally untyped (matches Product::scopePriceRange()'s own
 * convention): callers may pass either a Builder or a relation query such as
 * $shop->products() — Eloquent's Relation::__call() forwards scope/where
 * calls to the underlying builder but returns the *relation* instance when
 * the forwarded call returns the same builder, so a strict Builder type hint
 * here would throw a TypeError for relation-based callers like ShopController.
 */
trait FiltersProductListings
{
    protected function applyProductSortAndFilters($query, Request $request): void
    {
        // min_rating=0 is indistinguishable from "not provided" here, but a
        // 0★+ filter is a no-op either way, so this is harmless.
        if ($minRating = $request->integer('min_rating')) {
            $query->where('reviews_count', '>', 0)
                ->whereRaw('(rating_sum / reviews_count) >= ?', [$minRating]);
        }

        $query->priceRange($request->input('min_price'), $request->input('max_price'));

        match ($request->input('sort', 'latest')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating_desc' => $query->orderByRating(),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };
    }
}
