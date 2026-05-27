<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

test('guest cannot access orders page', function () {
    $this->get(route('orders.index'))->assertRedirect('/login');
});

test('authenticated user can view orders page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('orders.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Orders/Index'));
});

test('guest cannot access checkout', function () {
    $this->get(route('checkout.index'))->assertRedirect('/login');
});

test('checkout redirects to cart when cart is empty', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('checkout.index'))
        ->assertRedirect(route('cart.index'));
});

test('seller can view order details', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->get(route('seller.orders.show', $order))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Seller/Orders/Show'));
});

test('seller can update order status', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->paid()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->patch(route('seller.orders.status', $order), ['status' => 'processing'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('processing');
});

test('checkout page shows only items matching item_ids', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $shop2 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop1->id, 'stock' => 10]);
    $product2 = Product::factory()->create(['shop_id' => $shop2->id, 'stock' => 10]);

    $cart = Cart::create(['user_id' => $user->id]);
    $item1 = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 100]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 200]);

    $this->actingAs($user)
        ->get(route('checkout.index', ['item_ids' => [$item1->id]]))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Checkout/Index')
            ->has('cart.items', 1)
            ->where('itemIds', [$item1->id])
        );
});

test('checkout redirects to cart when no valid item_ids given', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]);

    $this->actingAs($user)
        ->get(route('checkout.index', ['item_ids' => [99999]]))
        ->assertRedirect(route('cart.index'));
});

test('partial checkout only removes selected items from cart', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $shop2 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop1->id, 'stock' => 10]);
    $product2 = Product::factory()->create(['shop_id' => $shop2->id, 'stock' => 10]);

    $cart = Cart::create(['user_id' => $user->id]);
    $item1 = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 100]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 200]);

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Test St',
            'payment_method' => 'simulated',
            'item_ids' => [$item1->id],
        ])
        ->assertRedirect(route('orders.index'));

    // shop2 item remains in cart
    expect($cart->fresh()->items()->count())->toBe(1);
    expect($cart->fresh()->items()->first()->product_id)->toBe($product2->id);

    // only one order created for shop1
    expect(Order::where('user_id', $user->id)->count())->toBe(1);
    expect(Order::where('user_id', $user->id)->first()->shop_id)->toBe($shop1->id);
});

test('checkout with multiple shops creates one order per shop', function () {
    $user = User::factory()->create();
    $shop1 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $shop2 = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product1 = Product::factory()->create(['shop_id' => $shop1->id, 'stock' => 10]);
    $product2 = Product::factory()->create(['shop_id' => $shop2->id, 'stock' => 10]);

    $cart = Cart::create(['user_id' => $user->id]);
    $item1 = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product1->id, 'quantity' => 1, 'unit_price' => 100]);
    $item2 = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product2->id, 'quantity' => 1, 'unit_price' => 200]);

    $this->actingAs($user)
        ->post(route('checkout.store'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Test St',
            'payment_method' => 'simulated',
            'item_ids' => [$item1->id, $item2->id],
        ])
        ->assertRedirect(route('orders.index'));

    expect(Order::where('user_id', $user->id)->count())->toBe(2);
    expect($cart->fresh()->items()->count())->toBe(0);
});

// ---- Order cancellation ----

function makeOrderWithItem(array $orderState = [], int $stock = 5, int $qty = 2): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => $stock]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(array_merge(['user_id' => $buyer->id, 'shop_id' => $shop->id], $orderState));
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => $qty,
        'unit_price' => 100,
        'subtotal' => 100 * $qty,
    ]);

    return compact('seller', 'shop', 'product', 'buyer', 'order');
}

test('buyer can directly cancel a pending order with a reason and stock is restored', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => 'pending'], stock: 5, qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Changed my mind'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', 'approved')->where('initiated_by', 'buyer')->count())->toBe(1);
});

test('buyer requesting cancellation on a processing order does not cancel it yet', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => 'processing'], stock: 5, qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Too slow'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('processing');
    expect($product->fresh()->stock)->toBe(5);
    expect($order->cancellations()->where('status', 'requested')->count())->toBe(1);
});

test('cancellation reason is required', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => 'pending']);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => ''])
        ->assertSessionHasErrors('reason');
});

test('buyer cannot request cancellation again after rejection', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => 'processing']);
    OrderCancellation::factory()->rejected()->create(['order_id' => $order->id]);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Trying again'])
        ->assertForbidden();
});

test('seller can approve a cancellation request which cancels the order and restores stock', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => 'processing'], stock: 5, qty: 2);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.approve', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->stock)->toBe(7);
});

test('seller can reject a cancellation request leaving the order unchanged', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => 'processing'], stock: 5, qty: 2);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.reject', $order), ['response_reason' => 'Already shipped'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('processing');
    expect($product->fresh()->stock)->toBe(5);
    expect($order->cancellations()->where('status', 'rejected')->count())->toBe(1);
});

test('seller can directly cancel an order with a reason', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => 'processing'], stock: 5, qty: 2);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $order), ['reason' => 'Out of stock'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('cancelled');
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', 'approved')->where('initiated_by', 'seller')->count())->toBe(1);
});

test('a different buyer cannot cancel someone elses order', function () {
    ['order' => $order] = makeOrderWithItem(['status' => 'pending']);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('orders.cancel', $order), ['reason' => 'Not mine'])
        ->assertForbidden();
});

test('a seller from another shop cannot manage a cancellation request', function () {
    ['order' => $order] = makeOrderWithItem(['status' => 'processing']);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);
    $otherSeller = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $otherSeller->id]);

    $this->actingAs($otherSeller)
        ->post(route('seller.orders.cancellation.approve', $order))
        ->assertForbidden();
});

test('seller double approve or reject handles missing cancellation gracefully (Bug 1)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => 'processing']);

    $response = $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.approve', $order));

    expect(in_array($response->getStatusCode(), [403, 409]))->toBeTrue();

    $response2 = $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.reject', $order), ['response_reason' => 'No reason']);

    expect(in_array($response2->getStatusCode(), [403, 409]))->toBeTrue();
});

test('seller cannot bypass buyer review via cancelAsSeller when pending cancellation exists (Bug 3)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => 'processing']);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $order), ['reason' => 'Seller cancel'])
        ->assertForbidden();
});

test('duplicate cancellation requests are handled idempotently (Bug 4)', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => 'processing']);

    $service = app(App\Services\OrderService::class);

    $service->requestCancellation($order, 'First try');
    $service->requestCancellation($order, 'Second try');

    expect($order->cancellations()->where('status', 'requested')->count())->toBe(1);
});
