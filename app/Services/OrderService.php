<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function createOrdersFromCart(Cart $cart, array $shippingData): array
    {
        $cart->load('items.product.shop', 'items.product.primaryImage');

        $itemsByShop = $cart->items->groupBy('product.shop_id');
        $orders = [];

        DB::transaction(function () use ($itemsByShop, $shippingData, $cart, &$orders) {
            foreach ($itemsByShop as $shopId => $items) {
                $subtotal = $items->sum(fn ($item) => $item->quantity * $item->unit_price);

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(8)) . '-' . time(),
                    'user_id' => $cart->user_id,
                    'shop_id' => $shopId,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_fee' => 0,
                    'total' => $subtotal,
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

                $orders[] = $order;
            }

            $cart->items()->delete();
        });

        return $orders;
    }

    public function cancelOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });
    }
}
