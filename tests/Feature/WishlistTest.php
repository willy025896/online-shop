<?php

use App\Models\Product;
use App\Models\User;
use App\Models\WishlistItem;

test('wishlist routes require authentication', function () {
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);

    $this->get(route('wishlist.index'))->assertRedirect(route('login'));
    $this->post(route('wishlist.toggle'), ['product_id' => $product->id])->assertRedirect(route('login'));
    $this->delete(route('wishlist.destroy', $product))->assertRedirect(route('login'));
});

test('wishlist index page renders for authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('wishlist.index'))
        ->assertStatus(200);
});

test('toggle adds product to wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);

    $this->actingAs($user)
        ->post(route('wishlist.toggle'), ['product_id' => $product->id])
        ->assertRedirect();

    $this->assertDatabaseHas('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('toggle removes product from wishlist if already favorited', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);
    WishlistItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->post(route('wishlist.toggle'), ['product_id' => $product->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('duplicate wishlist toggle does not create duplicate', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);

    $this->actingAs($user)->post(route('wishlist.toggle'), ['product_id' => $product->id]);
    $this->actingAs($user)->post(route('wishlist.toggle'), ['product_id' => $product->id]);

    $this->assertDatabaseMissing('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);

    $this->assertEquals(0, WishlistItem::where('user_id', $user->id)->count());
});

test('wishlist destroy removes item', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);
    WishlistItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->delete(route('wishlist.destroy', $product))
        ->assertRedirect();

    $this->assertDatabaseMissing('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('wishlist destroy does not create item when product not favorited', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);

    $this->actingAs($user)
        ->delete(route('wishlist.destroy', $product))
        ->assertRedirect();

    $this->assertDatabaseMissing('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});

test('wishlist index only shows own items', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);

    WishlistItem::create(['user_id' => $other->id, 'product_id' => $product->id]);

    $response = $this->actingAs($user)
        ->get(route('wishlist.index'))
        ->assertStatus(200);

    $products = $response->original->getData()['page']['props']['products'];
    expect($products)->toBeEmpty();
});

test('adding to cart from wishlist keeps item in wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE, 'stock' => 5]);
    WishlistItem::create(['user_id' => $user->id, 'product_id' => $product->id]);

    $this->actingAs($user)
        ->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 1])
        ->assertRedirect();

    $this->assertDatabaseHas('wishlist_items', [
        'user_id' => $user->id,
        'product_id' => $product->id,
    ]);
});
