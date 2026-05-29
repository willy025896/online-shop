<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WishlistItem;
use Illuminate\Support\Facades\Auth;

class WishlistService
{
    public function toggle(Product $product): bool
    {
        $existing = WishlistItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        // firstOrCreate guards against the unique(user_id, product_id) constraint
        // throwing on concurrent/double-clicked add requests.
        WishlistItem::firstOrCreate([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);

        return true;
    }

    public function remove(Product $product): void
    {
        WishlistItem::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->delete();
    }

    public function getItemsWithProducts()
    {
        if (! Auth::check()) {
            return collect();
        }

        return Auth::user()
            ->favoritedProducts()
            ->with(['primaryImage', 'shop'])
            ->orderByPivot('created_at', 'desc')
            ->get();
    }

    public function favoritedProductIds(): array
    {
        if (! Auth::check()) {
            return [];
        }

        return WishlistItem::where('user_id', Auth::id())
            ->pluck('product_id')
            ->toArray();
    }
}
