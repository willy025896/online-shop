<?php

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\Http;

function fakeEcpaySuccessfulRefund(): void
{
    Http::fake([
        'payment-stage.ecpay.com.tw/*' => Http::response('RtnCode=1&RtnMsg=Succeeded'),
    ]);
}

function makeCompletedOrderWithItem(array $orderState = [], int $stock = 5, int $qty = 2, float $unitPrice = 100): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => $stock]);
    $buyer = User::factory()->create();
    $order = Order::factory()->completed()->create(array_merge([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'subtotal' => $qty * $unitPrice,
        'discount' => 0,
        'total' => $qty * $unitPrice,
        'completed_at' => now()->subDay(),
        'gateway_trade_no' => 'TEST'.uniqid(),
    ], $orderState));
    $item = $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => $qty,
        'unit_price' => $unitPrice,
        'subtotal' => $unitPrice * $qty,
    ]);

    return compact('seller', 'shop', 'product', 'buyer', 'order', 'item');
}

test('buyer can request a return within the window', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem();

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Defective',
            'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ])
        ->assertRedirect();

    expect($order->returns()->where('status', 'requested')->count())->toBe(1);
    expect($order->returns()->first()->items()->count())->toBe(1);
});

test('buyer cannot request return outside the return window', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(['completed_at' => now()->subDays(10)]);

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Too late',
            'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ])
        ->assertForbidden();

    expect($order->returns()->count())->toBe(0);
});

test('buyer cannot request return on a non-completed order', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(['status' => 'processing', 'completed_at' => null]);

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Changed my mind',
            'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

test('return request quantity cannot exceed the remaining returnable quantity', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Too many',
            'items' => [['order_item_id' => $item->id, 'quantity' => 3]],
        ])
        ->assertSessionHasErrors('items.0.quantity');

    expect($order->returns()->count())->toBe(0);
});

test('buyer cannot submit a new return request while one is pending', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem();
    OrderReturn::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Another try',
            'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

test('seller can approve a return which restores stock and refunds without changing order status', function () {
    fakeEcpaySuccessfulRefund();
    ['seller' => $seller, 'product' => $product, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(stock: 5, qty: 2, unitPrice: 100);
    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 1]);

    $this->actingAs($seller)
        ->post(route('seller.orders.returns.approve', $order))
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(6);
    expect($order->fresh()->status)->toBe('completed');
    expect((float) $order->fresh()->refunded_amount)->toBe(100.0);
    expect($return->fresh()->status)->toBe('approved');
    expect((float) $return->fresh()->refund_amount)->toBe(100.0);
});

test('seller can reject a return leaving stock and refund unchanged', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(stock: 5, qty: 2, unitPrice: 100);
    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 1]);

    $this->actingAs($seller)
        ->post(route('seller.orders.returns.reject', $order), ['response_reason' => 'Item not returned'])
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(5);
    expect((float) $order->fresh()->refunded_amount)->toBe(0.0);
    expect($return->fresh()->status)->toBe('rejected');
    expect($return->fresh()->response_reason)->toBe('Item not returned');
});

test('fully returning an order releases the coupon', function () {
    fakeEcpaySuccessfulRefund();
    ['seller' => $seller, 'buyer' => $buyer, 'product' => $product, 'order' => $order, 'item' => $item] =
        makeCompletedOrderWithItem(stock: 5, qty: 2, unitPrice: 100, orderState: ['discount' => 20]);

    $coupon = Coupon::factory()->create(['shop_id' => $order->shop_id, 'used_count' => 1]);
    CouponRedemption::create(['coupon_id' => $coupon->id, 'user_id' => $buyer->id, 'order_id' => $order->id, 'discount_amount' => 20]);

    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 2]);

    $this->actingAs($seller)
        ->post(route('seller.orders.returns.approve', $order))
        ->assertRedirect();

    // subtotal 200, discount 20 -> 10% ratio; full 200 items subtotal * 0.9 = 180
    expect((float) $return->fresh()->refund_amount)->toBe(180.0);
    expect($coupon->fresh()->used_count)->toBe(0);
    expect(CouponRedemption::where('order_id', $order->id)->exists())->toBeFalse();
});

test('partially returning an order does not release the coupon', function () {
    fakeEcpaySuccessfulRefund();
    ['seller' => $seller, 'buyer' => $buyer, 'product' => $product, 'order' => $order, 'item' => $item] =
        makeCompletedOrderWithItem(stock: 5, qty: 2, unitPrice: 100, orderState: ['discount' => 20]);

    $coupon = Coupon::factory()->create(['shop_id' => $order->shop_id, 'used_count' => 1]);
    CouponRedemption::create(['coupon_id' => $coupon->id, 'user_id' => $buyer->id, 'order_id' => $order->id, 'discount_amount' => 20]);

    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 1]);

    $this->actingAs($seller)
        ->post(route('seller.orders.returns.approve', $order))
        ->assertRedirect();

    // subtotal 200, discount 20 -> 10% ratio; only 1 unit (100) returned * 0.9 = 90
    expect((float) $return->fresh()->refund_amount)->toBe(90.0);
    expect($coupon->fresh()->used_count)->toBe(1);
    expect(CouponRedemption::where('order_id', $order->id)->exists())->toBeTrue();
});

test('duplicate order_item_id rows in one request cannot exceed the returnable quantity combined', function () {
    ['buyer' => $buyer, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Split into two rows',
            'items' => [
                ['order_item_id' => $item->id, 'quantity' => 2],
                ['order_item_id' => $item->id, 'quantity' => 2],
            ],
        ])
        ->assertSessionHasErrors(['items.0.quantity', 'items.1.quantity']);

    expect($order->returns()->count())->toBe(0);
});

test('a different buyer cannot request a return for someone elses order', function () {
    ['order' => $order, 'item' => $item] = makeCompletedOrderWithItem();
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('orders.returns.store', $order), [
            'reason' => 'Not mine',
            'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
        ])
        ->assertForbidden();
});

test('a seller from another shop cannot manage a return request', function () {
    ['order' => $order, 'item' => $item] = makeCompletedOrderWithItem();
    OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $otherSeller = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $otherSeller->id]);

    $this->actingAs($otherSeller)
        ->post(route('seller.orders.returns.approve', $order))
        ->assertForbidden();
});

test('duplicate return approval does not restore stock or refund twice', function () {
    fakeEcpaySuccessfulRefund();
    ['product' => $product, 'order' => $order, 'item' => $item] = makeCompletedOrderWithItem(stock: 5, qty: 2, unitPrice: 100);
    $return = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $return->items()->create(['order_item_id' => $item->id, 'quantity' => 1]);

    $seller = $order->shop->user;
    $service = app(App\Services\OrderService::class);

    $service->approveReturn($return, $seller);
    $service->approveReturn($return, $seller);

    expect($product->fresh()->stock)->toBe(6);
    expect((float) $order->fresh()->refunded_amount)->toBe(100.0);
});
