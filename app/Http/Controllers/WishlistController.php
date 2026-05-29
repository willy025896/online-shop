<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WishlistController extends Controller
{
    public function __construct(
        private WishlistService $wishlistService,
    ) {}

    public function index()
    {
        $products = $this->wishlistService->getItemsWithProducts();

        return Inertia::render('Wishlist/Index', [
            'products' => $products,
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::active()->findOrFail($request->product_id);

        $favorited = $this->wishlistService->toggle($product);

        return back()->with('success', $favorited ? 'Added to wishlist.' : 'Removed from wishlist.');
    }

    public function destroy(Product $product)
    {
        $this->wishlistService->remove($product);

        return back()->with('success', 'Removed from wishlist.');
    }
}
