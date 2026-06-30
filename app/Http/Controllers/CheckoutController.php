<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService,
        private ShippingService $shippingService,
    ) {}

    public function index()
    {
        $cart = $this->cartService->getCartWithItems();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $itemIds = array_map('intval', request()->input('item_ids', []));
        $selectedItems = $itemIds
            ? $cart->items->whereIn('id', $itemIds)->values()
            : $cart->items;

        if ($selectedItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'No items selected.');
        }

        // Shipping is evaluated per shop (matching the per-shop order split in
        // OrderService::createOrdersFromCart); ShippingService owns the rule.
        $shopBreakdown = $this->shippingService->breakdownForItems($selectedItems);

        $subtotal = $shopBreakdown->sum('subtotal');
        $shippingFee = $shopBreakdown->sum('shipping_fee');
        $totals = [
            'subtotal' => round($subtotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($subtotal + $shippingFee, 2),
        ];

        return Inertia::render('Checkout/Index', [
            'cart' => ['items' => $selectedItems],
            'totals' => $totals,
            'shopBreakdown' => $shopBreakdown,
            'shippingConfig' => $this->shippingService->publicConfig(),
            'user' => auth()->user(),
            'itemIds' => $selectedItems->pluck('id')->values()->all(),
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
            'item_ids' => 'array',
            'item_ids.*' => 'integer',
        ]);

        $cart = $this->cartService->getCartWithItems();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $itemIds = $validated['item_ids'] ?? [];

        try {
            $orders = $this->orderService->createOrdersFromCart($cart, $validated, $itemIds);

            return redirect()->route('orders.index')
                ->with('success', count($orders).' order(s) created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }
}
