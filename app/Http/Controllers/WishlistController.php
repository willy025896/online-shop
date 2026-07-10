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

        // Removing the last item on a non-first page (via destroy()'s back()
        // redirect) leaves the requested page beyond the recomputed last
        // page; bounce back to the last valid page instead of rendering an
        // empty list with no pagination nav to escape it.
        if ($products->currentPage() > $products->lastPage()) {
            return redirect()->route('wishlist.index', ['page' => $products->lastPage()]);
        }

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
