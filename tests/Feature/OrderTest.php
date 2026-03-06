<?php

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