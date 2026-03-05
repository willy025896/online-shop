<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function index()
    {
        $cart = $this->cartService->getCartWithItems();
        $totals = $this->cartService->calculateTotals($cart);

        return Inertia::render('Cart/Index', [
            'cart' => $cart,
            'totals' => $totals,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $product = Product::active()->findOrFail($request->product_id);

        if (!$product->inStock()) {
            return back()->withErrors(['product' => 'This product is out of stock.']);
        }

        $this->cartService->addItem($product, $request->input('quantity', 1));

        return back()->with('success', 'Item added to cart.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cart = $this->cartService->getOrCreateCart();
        abort_unless($cartItem->cart_id === $cart->id, 403);

        $this->cartService->updateItem($cartItem, $request->quantity);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $cartItem)
    {
        $cart = $this->cartService->getOrCreateCart();
        abort_unless($cartItem->cart_id === $cart->id, 403);

        $this->cartService->removeItem($cartItem);

        return back()->with('success', 'Item removed from cart.');
    }
}
