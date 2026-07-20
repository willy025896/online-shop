<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ShippingService;

beforeEach(function () {
    // Pin the rule so tests don't depend on env-tunable defaults.
    config(['shipping.flat_fee' => 100, 'shipping.free_threshold' => 1000]);
});

// ---- ShippingService unit ----

test('shipping service charges flat fee below the free threshold', function () {
    expect(app(ShippingService::class)->feeForSubtotal(500))->toBe(100.0);
});

test('shipping service is free at or above the free threshold', function () {
    $service = app(ShippingService::class);

    expect($service->feeForSubtotal(1000))->toBe(0.0);
    expect($service->feeForSubtotal(1500))->toBe(0.0);
});

test('shipping service always charges flat fee when free shipping is disabled', function () {
    config(['shipping.free_threshold' => null]);

    expect(app(ShippingService::class)->feeForSubtotal(99999))->toBe(100.0);
});

// ---- Order creation ----

function shippingCart(User $buyer): Cart
{
    return Cart::create(['user_id' => $buyer->id]);
}

test('order below the threshold is charged the flat shipping fee and total includes it', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    $cart = shippingCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $orders = app(OrderService::class)->createOrdersFromCart($cart, [
        'shipping_name' => 'Test', 'shipping_phone' => '0900000000', 'shipping_address' => 'Addr',
    ]);

    $order = $orders[0];
    expect((float) $order->subtotal)->toBe(500.0);
    expect((float) $order->shipping_fee)->toBe(100.0);
    expect((float) $order->total)->toBe(600.0);
});

test('order at or above the threshold ships free', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    $cart = shippingCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100]);

    $orders = app(OrderService::class)->createOrdersFromCart($cart, [
        'shipping_name' => 'Test', 'shipping_phone' => '0900000000', 'shipping_address' => 'Addr',
    ]);

    $order = $orders[0];
    expect((float) $order->subtotal)->toBe(1000.0);
    expect((float) $order->shipping_fee)->toBe(0.0);
    expect((float) $order->total)->toBe(1000.0);
});

test('shipping is evaluated independently per shop', function () {
    $buyer = User::factory()->create();
    $shopA = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $shopB = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $productA = Product::factory()->create(['shop_id' => $shopA->id, 'stock' => 10]);
    $productB = Product::factory()->create(['shop_id' => $shopB->id, 'stock' => 10]);

    $cart = shippingCart($buyer);
    // Shop A: 1200 → free; Shop B: 300 → charged 100
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $productA->id, 'quantity' => 4, 'unit_price' => 300]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $productB->id, 'quantity' => 3, 'unit_price' => 100]);

    $orders = app(OrderService::class)->createOrdersFromCart($cart, [
        'shipping_name' => 'Test', 'shipping_phone' => '0900000000', 'shipping_address' => 'Addr',
    ]);

    $byShop = collect($orders)->keyBy('shop_id');

    expect((float) $byShop[$shopA->id]->shipping_fee)->toBe(0.0);
    expect((float) $byShop[$shopA->id]->total)->toBe(1200.0);
    expect((float) $byShop[$shopB->id]->shipping_fee)->toBe(100.0);
    expect((float) $byShop[$shopB->id]->total)->toBe(400.0);
});

test('cart totals and checkout page survive a soft-deleted product in the cart', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $live = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $gone = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    $cart = shippingCart($buyer);
    $liveItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $live->id, 'quantity' => 2, 'unit_price' => 100]);
    $goneItem = CartItem::create(['cart_id' => $cart->id, 'product_id' => $gone->id, 'quantity' => 1, 'unit_price' => 50]);

    $gone->delete(); // soft delete — the cart item still references it

    // calculateTotals must not crash on the null product relation.
    $totals = app(CartService::class)->calculateTotals($cart->fresh());
    expect($totals['shipping_fee'])->toBe(100.0);

    // Checkout page renders without a 500 even with the dead item selected.
    $this->actingAs($buyer)
        ->withSession(['checkout_selected_item_ids' => [$liveItem->id, $goneItem->id]])
        ->get(route('checkout.index'))
        ->assertStatus(200);
});

test('checkout page exposes per-shop breakdown and shipping totals', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    $cart = shippingCart($buyer);
    $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]);

    $this->actingAs($buyer)
        ->withSession(['checkout_selected_item_ids' => [$item->id]])
        ->get(route('checkout.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Checkout/Index')
            ->where('totals.subtotal', 200)
            ->where('totals.shipping_fee', 100)
            ->where('totals.total', 300)
            ->has('shopBreakdown', 1)
            ->where('shopBreakdown.0.shipping_fee', 100)
        );
});
