<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

test('guest cannot access seller pages', function () {
    $this->get(route('seller.dashboard'))->assertRedirect('/login');
    $this->get(route('seller.products.index'))->assertRedirect('/login');
    $this->get(route('seller.orders.index'))->assertRedirect('/login');
});

test('customer cannot access seller pages', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user)
        ->get(route('seller.dashboard'))
        ->assertForbidden();
});

test('seller registration page can be rendered', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('seller.register'))
        ->assertStatus(200);
});

test('user can register as seller', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('seller.register.store'), [
            'name' => 'My Shop',
            'slug' => 'my-shop',
            'description' => 'A test shop',
        ])
        ->assertRedirect(route('seller.dashboard'));

    expect($user->fresh()->role)->toBe('seller');
    expect(Shop::where('slug', 'my-shop')->exists())->toBeTrue();
});

test('seller can access dashboard', function () {
    $user = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('seller.dashboard'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Dashboard')
            ->has('stats')
            ->has('shop')
        );
});

test('seller can view products list', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    Product::factory(3)->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->get(route('seller.products.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Products/Index')
            ->has('products.data', 3)
        );
});

test('seller can create a product', function () {
    $user = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->post(route('seller.products.store'), [
            'name' => 'Test Product',
            'price' => 29.99,
            'stock' => 10,
            'status' => 'active',
        ])
        ->assertRedirect();

    expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
});

test('seller can update a product', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->put(route('seller.products.update', $product), [
            'name' => 'Updated Product',
            'price' => 39.99,
            'stock' => 5,
            'status' => 'active',
        ])
        ->assertRedirect();

    expect($product->fresh()->name)->toBe('Updated Product');
});

test('seller can delete a product', function () {
    $user = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($user)
        ->delete(route('seller.products.destroy', $product))
        ->assertRedirect(route('seller.products.index'));

    expect($product->fresh()->trashed())->toBeTrue();
});

test('seller cannot edit another sellers product', function () {
    $user = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $user->id]);

    $otherShop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $otherShop->id]);

    $this->actingAs($user)
        ->get(route('seller.products.edit', $product))
        ->assertForbidden();
});

test('seller can view shop edit page', function () {
    $user = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('seller.shop.edit'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Seller/Shop/Edit'));
});