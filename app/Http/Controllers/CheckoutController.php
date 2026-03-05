<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
    ) {}

    public function index()
    {
        $cart = $this->cartService->getCartWithItems();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $totals = $this->cartService->calculateTotals($cart);

        return Inertia::render('Checkout/Index', [
            'cart' => $cart,
            'totals' => $totals,
            'user' => auth()->user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'string|in:simulated',
            'notes' => 'nullable|string|max:500',
        ]);

        $cart = $this->cartService->getCartWithItems();

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        try {
            $orders = $this->orderService->createOrdersFromCart($cart, $validated);

            return redirect()->route('orders.index')
                ->with('success', count($orders) . ' order(s) created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }
}
