<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancellationRequestedNotification;
use App\Notifications\OrderCancellationRespondedNotification;
use App\Notifications\OrderCancelledBySellerNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private ShippingService $shippingService,
        private CouponService $couponService,
    ) {}

    /**
     * @param  array<int|string, string>  $appliedCoupons  map of shop_id => coupon code
     */
    public function createOrdersFromCart(Cart $cart, array $shippingData, array $itemIds = [], array $appliedCoupons = []): array
    {
        $cart->load('items.product.shop', 'items.product.primaryImage');

        $items = $itemIds
            ? $cart->items->whereIn('id', $itemIds)
            : $cart->items;

        $itemsByShop = $items->groupBy('product.shop_id');
        $orders = [];

        // Coupons are user-scoped (per-user limit + redemption record). Checkout
        // is auth-only, so the cart always has a user when a coupon is applied.
        $userId = (int) $cart->user_id;

        DB::transaction(function () use ($itemsByShop, $shippingData, $cart, $itemIds, $appliedCoupons, $userId, &$orders) {
            foreach ($itemsByShop as $shopId => $items) {
                $subtotal = $items->sum(fn ($item) => $item->quantity * $item->unit_price);
                // Shipping (and its free-shipping threshold) is evaluated on the
                // pre-discount subtotal — the coupon discounts goods, not shipping.
                $shippingFee = $this->shippingService->feeForSubtotal($subtotal);

                $coupon = null;
                $discount = 0.0;
                if (isset($appliedCoupons[$shopId]) && $appliedCoupons[$shopId] !== '') {
                    // Re-validate server-side (source of truth); a stale/exhausted
                    // code throws CouponException and rolls back the whole checkout.
                    // NOTE: v1 only supports shop-scoped coupons, so a code maps to
                    // exactly one shop. If platform-wide coupons (shop_id null) are
                    // enabled later, guard here against the same coupon being applied
                    // to multiple shops in one checkout (double redeem / self-rollback).
                    $coupon = $this->couponService->validate($appliedCoupons[$shopId], (int) $shopId, (float) $subtotal, $userId);
                    $discount = $this->couponService->discountFor($coupon, (float) $subtotal);
                }

                $order = Order::create([
                    'order_number' => 'ORD-'.strtoupper(Str::random(8)).'-'.time(),
                    'user_id' => $cart->user_id,
                    'shop_id' => $shopId,
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'status' => Order::STATUS_PENDING,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'shipping_fee' => $shippingFee,
                    'total' => $subtotal - $discount + $shippingFee,
                    'shipping_name' => $shippingData['shipping_name'],
                    'shipping_phone' => $shippingData['shipping_phone'],
                    'shipping_address' => $shippingData['shipping_address'],
                    'payment_method' => $shippingData['payment_method'] ?? 'simulated',
                    'notes' => $shippingData['notes'] ?? null,
                ]);

                foreach ($items as $cartItem) {
                    $product = Product::lockForUpdate()->find($cartItem->product_id);

                    if ($product->stock < $cartItem->quantity) {
                        throw new \Exception("Insufficient stock for product: {$product->name}");
                    }

                    $product->decrement('stock', $cartItem->quantity);

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_image' => $product->primaryImage?->path,
                        'quantity' => $cartItem->quantity,
                        'unit_price' => $cartItem->unit_price,
                        'subtotal' => $cartItem->quantity * $cartItem->unit_price,
                    ]);
                }

                if ($coupon !== null) {
                    $this->couponService->redeem($coupon, $userId, $order, $discount);
                }

                $orders[] = $order;
            }

            if ($itemIds) {
                $cart->items()->whereIn('id', $itemIds)->delete();
            } else {
                $cart->items()->delete();
            }
        });

        return $orders;
    }

    public function directCancelByBuyer(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            $lockedOrder = Order::lockForUpdate()->find($order->id);

            if (! $lockedOrder->canBeCancelledDirectly()) {
                return; // idempotent — silently skip duplicates
            }

            $lockedOrder->cancellations()->create([
                'initiated_by' => OrderCancellation::INITIATED_BY_BUYER,
                'status' => OrderCancellation::STATUS_APPROVED,
                'reason' => $reason,
                'responded_at' => now(),
            ]);

            $this->finalizeCancellation($lockedOrder);
        });
    }

    public function requestCancellation(Order $order, string $reason): void
    {
        DB::transaction(function () use ($order, $reason) {
            // Lock the order row to serialize concurrent requests
            $lockedOrder = Order::lockForUpdate()->find($order->id);

            if (! $lockedOrder->canRequestCancellation()) {
                return; // idempotent — silently skip duplicates
            }

            $lockedOrder->cancellations()->create([
                'initiated_by' => OrderCancellation::INITIATED_BY_BUYER,
                'status' => OrderCancellation::STATUS_REQUESTED,
                'reason' => $reason,
            ]);

            $lockedOrder->loadMissing('shop.user');
            $lockedOrder->shop?->user?->notify(new OrderCancellationRequestedNotification($lockedOrder));
        });
    }

    public function approveCancellation(OrderCancellation $cancellation, User $responder): void
    {
        DB::transaction(function () use ($cancellation, $responder) {
            $locked = OrderCancellation::lockForUpdate()->find($cancellation->id);

            if ($locked->status !== OrderCancellation::STATUS_REQUESTED) {
                return; // already handled by a concurrent request
            }

            $locked->update([
                'status' => OrderCancellation::STATUS_APPROVED,
                'responder_id' => $responder->id,
                'responded_at' => now(),
            ]);

            $this->finalizeCancellation($locked->order);

            $locked->order->loadMissing('user');
            $locked->order->user?->notify(new OrderCancellationRespondedNotification($locked));
        });
    }

    public function rejectCancellation(OrderCancellation $cancellation, User $responder, string $responseReason): void
    {
        DB::transaction(function () use ($cancellation, $responder, $responseReason) {
            $locked = OrderCancellation::lockForUpdate()->find($cancellation->id);

            if ($locked->status !== OrderCancellation::STATUS_REQUESTED) {
                return; // already handled by a concurrent request
            }

            $locked->update([
                'status' => OrderCancellation::STATUS_REJECTED,
                'responder_id' => $responder->id,
                'response_reason' => $responseReason,
                'responded_at' => now(),
            ]);

            $locked->order->loadMissing('user');
            $locked->order->user?->notify(new OrderCancellationRespondedNotification($locked));
        });
    }

    public function cancelBySeller(Order $order, User $seller, string $reason): void
    {
        DB::transaction(function () use ($order, $seller, $reason) {
            $lockedOrder = Order::lockForUpdate()->find($order->id);

            if (! $lockedOrder->canBeCancelledBySeller()) {
                return; // idempotent — skip if shipped/terminal or awaiting buyer review
            }

            $lockedOrder->cancellations()->create([
                'initiated_by' => OrderCancellation::INITIATED_BY_SELLER,
                'status' => OrderCancellation::STATUS_APPROVED,
                'reason' => $reason,
                'responder_id' => $seller->id,
                'responded_at' => now(),
            ]);

            $this->finalizeCancellation($lockedOrder);

            $lockedOrder->loadMissing('user');
            $lockedOrder->user?->notify(new OrderCancelledBySellerNotification($lockedOrder));
        });
    }

    private function finalizeCancellation(Order $order): void
    {
        if ($order->status === Order::STATUS_CANCELLED) {
            return;
        }

        foreach ($order->items as $item) {
            Product::where('id', $item->product_id)
                ->increment('stock', $item->quantity);
        }

        // Release any coupon consumed by this order so a cancellation doesn't
        // permanently burn the buyer's per-user allowance or the total budget.
        $this->couponService->releaseForOrder($order);

        $order->update(['status' => Order::STATUS_CANCELLED]);
    }
}
