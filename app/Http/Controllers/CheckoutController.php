<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Services\AddressService;
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
        private AddressService $addressService,
    ) {}

    public function storeSelection(Request $request)
    {
        $validated = $request->validate([
            'item_ids' => 'array',
            'item_ids.*' => 'integer',
        ]);

        session(['checkout_selected_item_ids' => $validated['item_ids'] ?? []]);

        return redirect()->route('checkout.index');
    }

    public function index()
    {
        $cart = $this->cartService->getCartWithItems();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $itemIds = array_map('intval', session('checkout_selected_item_ids', []));
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
            'addresses' => $this->addressService->listForUser(auth()->user()),
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
            'coupons' => 'array', // map of shop_id => coupon code
            'coupons.*' => 'string|max:50',
            'save_address' => 'boolean',
        ]);

        $cart = $this->cartService->getCartWithItems();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $itemIds = $validated['item_ids'] ?? [];

        try {
            $orders = $this->orderService->createOrdersFromCart($cart, $validated, $itemIds, $validated['coupons'] ?? []);
        } catch (CouponException $e) {
            return back()->withErrors(['checkout' => $e->translatedMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        // The order is already committed at this point — a failure saving the
        // address book entry must never surface as a "checkout failed" error.
        if ($request->boolean('save_address')) {
            try {
                $this->addressService->create(auth()->user(), [
                    'recipient_name' => $validated['shipping_name'],
                    'phone' => $validated['shipping_phone'],
                    'address' => $validated['shipping_address'],
                ]);
            } catch (\Exception $e) {
                report($e);
            }
        }

        session()->forget('checkout_selected_item_ids');

        return redirect()->route('orders.index')
            ->with('success', count($orders).' order(s) created successfully.');
    }
}
