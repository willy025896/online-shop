<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Product;
use App\Models\ProductVariant;
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
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_PROCESSING);
});

test('seller can ship an order with shipment details', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->paid()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->patch(route('seller.orders.status', $order), [
            'status' => Order::STATUS_SHIPPED,
            'carrier' => 'black_cat',
            'tracking_number' => 'TRACK123',
        ])
        ->assertRedirect();

    $fresh = $order->fresh();

    expect($fresh->status)->toBe(Order::STATUS_SHIPPED)
        ->and($fresh->carrier)->toBe('black_cat')
        ->and($fresh->tracking_number)->toBe('TRACK123');
});

test('seller cannot ship an order with a carrier outside the whitelist', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->paid()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->patch(route('seller.orders.status', $order), [
            'status' => Order::STATUS_SHIPPED,
            'carrier' => 'Not A Real Carrier',
            'tracking_number' => 'TRACK123',
        ])
        ->assertSessionHasErrors('carrier');

    expect($order->fresh()->carrier)->toBeNull();
});

test('seller can clear previously filled shipment details', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->paid()->create(['shop_id' => $shop->id, 'carrier' => 'black_cat', 'tracking_number' => 'TRACK123']);

    $this->actingAs($user)
        ->patch(route('seller.orders.status', $order), [
            'status' => Order::STATUS_SHIPPED,
            'carrier' => null,
            'tracking_number' => null,
        ])
        ->assertRedirect();

    $fresh = $order->fresh();

    expect($fresh->status)->toBe(Order::STATUS_SHIPPED)
        ->and($fresh->carrier)->toBeNull()
        ->and($fresh->tracking_number)->toBeNull();
});

test('seller can edit shipment details after the order has shipped', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id, 'status' => Order::STATUS_SHIPPED, 'carrier' => 'black_cat', 'tracking_number' => 'TRACK123']);

    $this->actingAs($user)
        ->patch(route('seller.orders.shipment', $order), [
            'carrier' => 'hct',
            'tracking_number' => 'CORRECTED456',
        ])
        ->assertRedirect();

    $fresh = $order->fresh();

    expect($fresh->status)->toBe(Order::STATUS_SHIPPED)
        ->and($fresh->carrier)->toBe('hct')
        ->and($fresh->tracking_number)->toBe('CORRECTED456');
});

test('seller cannot edit shipment details before the order has shipped', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id, 'status' => Order::STATUS_PROCESSING]);

    $this->actingAs($user)
        ->patch(route('seller.orders.shipment', $order), [
            'carrier' => 'hct',
            'tracking_number' => 'TRACK123',
        ])
        ->assertStatus(422);

    expect($order->fresh()->carrier)->toBeNull();
});

test('a seller from another shop cannot edit another shops shipment details', function () {
    $owner = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $owner->id]);
    $order = Order::factory()->create(['shop_id' => $shop->id, 'status' => Order::STATUS_SHIPPED, 'carrier' => 'black_cat', 'tracking_number' => 'TRACK123']);

    $otherSeller = User::factory()->seller()->create();

    $this->actingAs($otherSeller)
        ->patch(route('seller.orders.shipment', $order), [
            'carrier' => 'hct',
            'tracking_number' => 'HIJACKED',
        ])
        ->assertForbidden();

    expect($order->fresh()->carrier)->toBe('black_cat');
});

test('buyer order show page includes shipment details when available', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_SHIPPED, 'carrier' => 'black_cat', 'tracking_number' => 'TRACK123']);

    $this->actingAs($buyer)
        ->get(route('orders.show', $order))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Orders/Show')
            ->where('order.carrier', 'black_cat')
            ->where('order.tracking_number', 'TRACK123')
        );
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
        ->withSession(['checkout_selected_item_ids' => [$item1->id]])
        ->get(route('checkout.index'))
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
        ->withSession(['checkout_selected_item_ids' => [99999]])
        ->get(route('checkout.index'))
        ->assertRedirect(route('cart.index'));
});

test('checkout selection store persists item_ids into session and redirects to checkout', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);

    $cart = Cart::create(['user_id' => $user->id]);
    $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 50]);

    $this->actingAs($user)
        ->post(route('checkout.selection.store'), ['item_ids' => [$item->id]])
        ->assertRedirect(route('checkout.index'));

    expect(session('checkout_selected_item_ids'))->toBe([$item->id]);
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
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PENDING], stock: 5, qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Changed my mind'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_APPROVED)->where('initiated_by', OrderCancellation::INITIATED_BY_BUYER)->count())->toBe(1);
});

test('buyer requesting cancellation on a processing order does not cancel it yet', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING], stock: 5, qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Too slow'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_PROCESSING);
    expect($product->fresh()->stock)->toBe(5);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_REQUESTED)->count())->toBe(1);
});

test('cancellation reason is required', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PENDING]);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => ''])
        ->assertSessionHasErrors('reason');
});

test('buyer cannot cancel a shipped order', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_SHIPPED]);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Too late'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(Order::STATUS_SHIPPED);
    expect($order->cancellations()->count())->toBe(0);
});

test('buyer cannot request cancellation again after rejection', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);
    OrderCancellation::factory()->rejected()->create(['order_id' => $order->id]);

    $this->actingAs($buyer)
        ->post(route('orders.cancel', $order), ['reason' => 'Trying again'])
        ->assertForbidden();
});

test('seller can approve a cancellation request which cancels the order and restores stock', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING], stock: 5, qty: 2);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.approve', $order))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect($product->fresh()->stock)->toBe(7);
});

test('seller can reject a cancellation request leaving the order unchanged', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING], stock: 5, qty: 2);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.reject', $order), ['response_reason' => 'Already shipped'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_PROCESSING);
    expect($product->fresh()->stock)->toBe(5);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_REJECTED)->count())->toBe(1);
});

test('seller cannot directly cancel a shipped order', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_SHIPPED], stock: 5, qty: 2);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $order), ['reason' => 'Recall'])
        ->assertForbidden();

    expect($order->fresh()->status)->toBe(Order::STATUS_SHIPPED);
    expect($product->fresh()->stock)->toBe(5);
    expect($order->cancellations()->count())->toBe(0);
});

test('seller can directly cancel an order with a reason', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING], stock: 5, qty: 2);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $order), ['reason' => 'Out of stock'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_APPROVED)->where('initiated_by', OrderCancellation::INITIATED_BY_SELLER)->count())->toBe(1);
});

test('a different buyer cannot cancel someone elses order', function () {
    ['order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PENDING]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('orders.cancel', $order), ['reason' => 'Not mine'])
        ->assertForbidden();
});

test('a seller from another shop cannot manage a cancellation request', function () {
    ['order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);
    $otherSeller = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $otherSeller->id]);

    $this->actingAs($otherSeller)
        ->post(route('seller.orders.cancellation.approve', $order))
        ->assertForbidden();
});

test('seller double approve or reject handles missing cancellation gracefully (Bug 1)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);

    $response = $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.approve', $order));

    expect(in_array($response->getStatusCode(), [403, 409]))->toBeTrue();

    $response2 = $this->actingAs($seller)
        ->post(route('seller.orders.cancellation.reject', $order), ['response_reason' => 'No reason']);

    expect(in_array($response2->getStatusCode(), [403, 409]))->toBeTrue();
});

test('seller cannot bypass buyer review via cancelAsSeller when pending cancellation exists (Bug 3)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->post(route('seller.orders.cancel', $order), ['reason' => 'Seller cancel'])
        ->assertForbidden();
});

test('duplicate cancellation requests are handled idempotently (Bug 4)', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);

    $service = app(App\Services\OrderService::class);

    $service->requestCancellation($order, 'First try');
    $service->requestCancellation($order, 'Second try');

    expect($order->cancellations()->where('status', OrderCancellation::STATUS_REQUESTED)->count())->toBe(1);
});

test('duplicate direct buyer cancellations do not restore stock twice (Bug 5)', function () {
    ['product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PAID], stock: 5, qty: 2);

    $service = app(App\Services\OrderService::class);

    $service->directCancelByBuyer($order, 'First try');
    $service->directCancelByBuyer($order, 'Second try');

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_APPROVED)->count())->toBe(1);
});

test('duplicate direct seller cancellations do not restore stock twice (Bug 5)', function () {
    ['seller' => $seller, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING], stock: 5, qty: 2);

    $service = app(App\Services\OrderService::class);

    $service->cancelBySeller($order, $seller, 'First try');
    $service->cancelBySeller($order, $seller, 'Second try');

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    expect($product->fresh()->stock)->toBe(7);
    expect($order->cancellations()->where('status', OrderCancellation::STATUS_APPROVED)->count())->toBe(1);
});

test('seller cannot revive a cancelled order via status update (case 1)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_CANCELLED]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
});

test('seller cannot move order status backwards (case 4)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_SHIPPED]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe(Order::STATUS_SHIPPED);
});

test('seller cannot update status while a buyer cancellation awaits review (case 5)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PROCESSING]);
    OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_SHIPPED])
        ->assertStatus(422);

    expect($order->fresh()->status)->toBe(Order::STATUS_PROCESSING);
});

test('seller can ship an unpaid order for cash-on-delivery (case 2 stays allowed)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PENDING]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_SHIPPED])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_SHIPPED);
});

test('seller can complete a paid order directly for virtual goods (case 3 stays allowed)', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PAID]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_COMPLETED])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(Order::STATUS_COMPLETED);
});

test('status transitions are recorded in the status log', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PAID]);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
        ->assertRedirect();

    $log = $order->statusLogs()->latest('id')->first();

    expect($log)->not->toBeNull();
    expect($log->from_status)->toBe(Order::STATUS_PAID);
    expect($log->to_status)->toBe(Order::STATUS_PROCESSING);
    expect($log->changed_by)->toBe($seller->id);
});

test('cancellation is also recorded in the status log', function () {
    ['order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PAID]);

    app(App\Services\OrderService::class)->directCancelByBuyer($order, 'Changed my mind');

    expect($order->statusLogs()->where('to_status', Order::STATUS_CANCELLED)->count())->toBe(1);
});

test('non-admin users cannot access admin order show', function () {
    ['buyer' => $buyer, 'seller' => $seller, 'order' => $order] = makeOrderWithItem();

    $this->get(route('admin.orders.show', $order))->assertRedirect('/login');

    $this->actingAs($buyer)
        ->get(route('admin.orders.show', $order))
        ->assertForbidden();

    $this->actingAs($seller)
        ->get(route('admin.orders.show', $order))
        ->assertForbidden();
});

// ---- Reorder ----

test('buyer can reorder an order whose products are still available', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 2);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect(route('cart.index'));

    $cart = Cart::where('user_id', $buyer->id)->first();

    expect($cart)->not->toBeNull();
    expect($cart->items()->count())->toBe(1);
    expect($cart->items()->first()->product_id)->toBe($product->id);
    expect($cart->items()->first()->quantity)->toBe(2);
});

test('reorder merges into an existing cart item quantity', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 10, qty: 2);

    $cart = Cart::create(['user_id' => $buyer->id]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100]);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect(route('cart.index'));

    expect($cart->fresh()->items()->count())->toBe(1);
    expect($cart->fresh()->items()->first()->quantity)->toBe(5);
});

test('reorder skips a product that has been deactivated', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 2);
    $product->update(['status' => Product::STATUS_INACTIVE]);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart === null || $cart->items()->count() === 0)->toBeTrue();
});

test('reorder skips a product that has been soft-deleted', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 2);
    $product->delete();

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart === null || $cart->items()->count() === 0)->toBeTrue();
});

test('reorder skips a variant that has been soft-deleted but keeps other items', function () {
    ['shop' => $shop, 'buyer' => $buyer, 'product' => $availableProduct, 'order' => $order] =
        makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 1);

    $variantProduct = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 5]);
    $variant = ProductVariant::factory()->create(['product_id' => $variantProduct->id, 'stock' => 5]);
    $order->items()->create([
        'product_id' => $variantProduct->id,
        'product_variant_id' => $variant->id,
        'product_name' => $variantProduct->name,
        'variant_label' => 'Size: M',
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
    ]);

    $variant->delete();

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect(route('cart.index'));

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart->items()->count())->toBe(1);
    expect($cart->items()->first()->product_id)->toBe($availableProduct->id);
});

test('reorder skips a variant-less item when the product has since gained variants', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 2);
    ProductVariant::factory()->create(['product_id' => $product->id, 'stock' => 5]);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart === null || $cart->items()->count() === 0)->toBeTrue();
});

test('reorder skips a product that is completely out of stock', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 2);
    $product->update(['stock' => 0]);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect()
        ->assertSessionHas('error');

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart === null || $cart->items()->count() === 0)->toBeTrue();
});

test('reorder clamps quantity to remaining stock instead of skipping the item', function () {
    ['buyer' => $buyer, 'product' => $product, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED], stock: 5, qty: 5);
    $product->update(['stock' => 2]);

    $this->actingAs($buyer)
        ->post(route('orders.reorder', $order))
        ->assertRedirect(route('cart.index'));

    $cart = Cart::where('user_id', $buyer->id)->first();
    expect($cart->items()->first()->quantity)->toBe(2);
});

test('a different buyer cannot reorder someone elses order', function () {
    ['order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED]);
    $other = User::factory()->create();

    $this->actingAs($other)
        ->post(route('orders.reorder', $order))
        ->assertForbidden();
});

test('seller cannot reorder a buyers order', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_COMPLETED]);

    $this->actingAs($seller)
        ->post(route('orders.reorder', $order))
        ->assertForbidden();
});

test('admin can view order show with status logs loaded', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderWithItem(['status' => Order::STATUS_PAID]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => Order::STATUS_PROCESSING])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.orders.show', $order))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Orders/Show')
            ->where('order.id', $order->id)
            ->has('order.status_logs', 1)
            ->where('order.status_logs.0.from_status', Order::STATUS_PAID)
            ->where('order.status_logs.0.to_status', Order::STATUS_PROCESSING)
            ->where('order.status_logs.0.changed_by.id', $seller->id)
        );
});
