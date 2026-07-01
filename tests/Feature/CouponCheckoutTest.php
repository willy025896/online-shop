<?php

use App\Exceptions\CouponException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\OrderService;

function couponCart(User $buyer): Cart
{
    return Cart::create(['user_id' => $buyer->id]);
}

$shippingData = [
    'shipping_name' => 'Test', 'shipping_phone' => '0900000000', 'shipping_address' => 'Addr',
];

test('a percentage coupon discounts the shop subtotal and adjusts the total', function () use ($shippingData) {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $coupon = Coupon::factory()->percentage(10)->create(['shop_id' => $shop->id, 'code' => 'SAVE10']);

    $cart = couponCart($buyer);
    // subtotal 500 → free-shipping threshold (1000) not met → 100 shipping
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $orders = app(OrderService::class)->createOrdersFromCart($cart, $shippingData, [], [$shop->id => 'save10']);
    $order = $orders[0];

    expect((float) $order->subtotal)->toBe(500.0);
    expect((float) $order->discount)->toBe(50.0);
    expect((float) $order->shipping_fee)->toBe(100.0); // shipping unaffected by discount
    expect((float) $order->total)->toBe(550.0);        // 500 - 50 + 100
    expect($order->coupon_id)->toBe($coupon->id);
    expect($order->coupon_code)->toBe('SAVE10');

    expect($coupon->fresh()->used_count)->toBe(1);
    expect(CouponRedemption::where('order_id', $order->id)->value('discount_amount'))->toEqual(50.0);
});

test('a coupon only discounts its own shop order in a multi-shop checkout', function () use ($shippingData) {
    $buyer = User::factory()->create();
    $shopA = Shop::factory()->create();
    $shopB = Shop::factory()->create();
    $productA = Product::factory()->create(['shop_id' => $shopA->id, 'stock' => 10]);
    $productB = Product::factory()->create(['shop_id' => $shopB->id, 'stock' => 10]);
    Coupon::factory()->fixed(100)->create(['shop_id' => $shopA->id, 'code' => 'A100']);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $productA->id, 'quantity' => 5, 'unit_price' => 100]); // 500
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $productB->id, 'quantity' => 5, 'unit_price' => 100]); // 500

    $orders = app(OrderService::class)->createOrdersFromCart($cart, $shippingData, [], [$shopA->id => 'A100']);
    $byShop = collect($orders)->keyBy('shop_id');

    expect((float) $byShop[$shopA->id]->discount)->toBe(100.0);
    expect((float) $byShop[$shopA->id]->total)->toBe(500.0); // 500 - 100 + 100 shipping
    expect((float) $byShop[$shopB->id]->discount)->toBe(0.0);
    expect((float) $byShop[$shopB->id]->total)->toBe(600.0); // untouched
});

test('an exhausted coupon aborts the whole checkout and rolls back', function () use ($shippingData) {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    Coupon::factory()->create(['shop_id' => $shop->id, 'code' => 'GONE', 'usage_limit' => 1, 'used_count' => 1]);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    expect(fn () => app(OrderService::class)->createOrdersFromCart($cart, $shippingData, [], [$shop->id => 'GONE']))
        ->toThrow(CouponException::class);

    // rolled back: no order, no stock decrement
    expect(App\Models\Order::count())->toBe(0);
    expect($product->fresh()->stock)->toBe(10);
});

test('checkout store applies a coupon end to end', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    Coupon::factory()->fixed(50)->create(['shop_id' => $shop->id, 'code' => 'MINUS50']);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $this->actingAs($buyer)->post(route('checkout.store'), [
        'shipping_name' => 'Test', 'shipping_phone' => '0900000000', 'shipping_address' => 'Addr',
        'coupons' => [$shop->id => 'MINUS50'],
    ])->assertRedirect(route('orders.index'));

    $order = App\Models\Order::first();
    expect((float) $order->discount)->toBe(50.0);
    expect((float) $order->total)->toBe(550.0); // 500 - 50 + 100
});

test('cancelling an order restores the coupon usage', function () use ($shippingData) {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $coupon = Coupon::factory()->fixed(50)->create([
        'shop_id' => $shop->id, 'code' => 'ONCE', 'per_user_limit' => 1,
    ]);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $orders = app(OrderService::class)->createOrdersFromCart($cart, $shippingData, [], [$shop->id => 'ONCE']);
    expect($coupon->fresh()->used_count)->toBe(1);

    // Cancel the order → coupon usage should be released.
    app(OrderService::class)->directCancelByBuyer($orders[0], 'changed my mind');

    expect($coupon->fresh()->used_count)->toBe(0);
    expect(CouponRedemption::where('order_id', $orders[0]->id)->exists())->toBeFalse();
    // The buyer can now use the once-per-user coupon again.
    expect(fn () => app(App\Services\CouponService::class)->validate('ONCE', $shop->id, 500, $buyer->id))
        ->not->toThrow(CouponException::class);
});

test('coupon preview endpoint returns the discount for a valid code', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    Coupon::factory()->percentage(20)->create(['shop_id' => $shop->id, 'code' => 'TAKE20']);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $this->actingAs($buyer)->postJson(route('checkout.coupon.preview'), [
        'code' => 'take20', 'shop_id' => $shop->id,
    ])->assertOk()->assertJson(['valid' => true, 'discount' => 100.0]);
});

test('coupon preview endpoint reports an invalid code', function () {
    $buyer = User::factory()->create();
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    $cart = couponCart($buyer);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]);

    $this->actingAs($buyer)->postJson(route('checkout.coupon.preview'), [
        'code' => 'NOPE', 'shop_id' => $shop->id,
    ])->assertOk()->assertJson(['valid' => false]);
});
