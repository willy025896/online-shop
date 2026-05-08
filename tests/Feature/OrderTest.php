<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
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