<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;

class CartService
{
    public function __construct(
        private ShippingService $shippingService,
    ) {}

    public function getOrCreateCart(): Cart
    {
        if (Auth::check()) {
            return Cart::firstOrCreate(['user_id' => Auth::id()]);
        }

        $sessionId = session()->getId();

        return Cart::firstOrCreate(['session_id' => $sessionId]);
    }

    public function addItem(Product $product, int $quantity = 1, ?ProductVariant $variant = null): CartItem
    {
        $cart = $this->getOrCreateCart();
        $unitPrice = $variant?->price ?? $product->price;

        $item = $this->matchVariant(
            CartItem::where('cart_id', $cart->id)->where('product_id', $product->id),
            $variant?->id,
        )->first();

        if ($item) {
            $item->update([
                'quantity' => $item->quantity + $quantity,
                'unit_price' => $unitPrice,
            ]);
        } else {
            $item = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        return $item->fresh();
    }

    /**
     * Scope a cart-items query to rows matching the given variant (or, when
     * null, rows with no variant at all) — a plain `where('product_variant_id', null)`
     * would compile to `= NULL`, which never matches, so the null case needs `whereNull`.
     */
    private function matchVariant($query, ?int $variantId)
    {
        return $variantId
            ? $query->where('product_variant_id', $variantId)
            : $query->whereNull('product_variant_id');
    }

    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        $item->update(['quantity' => $quantity]);

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $item->delete();
    }

    public function getCartWithItems(): ?Cart
    {
        $cart = $this->getOrCreateCart();
        $cart->load('items.product.images', 'items.product.shop', 'items.variant.optionValues.option');

        return $cart;
    }

    public function getCartCount(): int
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
        } else {
            $cart = Cart::where('session_id', session()->getId())->first();
        }

        return $cart ? $cart->items()->sum('quantity') : 0;
    }

    public function mergeGuestCart(): void
    {
        if (! Auth::check()) {
            return;
        }

        $sessionId = session()->getId();
        $guestCart = Cart::where('session_id', $sessionId)->first();

        if (! $guestCart) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        foreach ($guestCart->items as $guestItem) {
            $existingItem = $this->matchVariant(
                $userCart->items()->where('product_id', $guestItem->product_id),
                $guestItem->product_variant_id,
            )->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $guestItem->quantity,
                ]);
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        $guestCart->delete();
    }

    public function calculateTotals(Cart $cart): array
    {
        $items = $cart->items()->with('product.shop')->get();
        $subtotal = $items->sum(fn ($item) => $item->quantity * $item->unit_price);

        // ShippingService evaluates shipping per shop (mirroring the per-shop
        // order split at checkout); sum the per-shop fees from its breakdown.
        $shippingFee = $this->shippingService->breakdownForItems($items)->sum('shipping_fee');

        return [
            'subtotal' => round($subtotal, 2),
            'shipping_fee' => round($shippingFee, 2),
            'total' => round($subtotal + $shippingFee, 2),
        ];
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
    }
}
