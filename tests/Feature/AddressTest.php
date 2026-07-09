<?php

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;

test('address routes require authentication', function () {
    $address = Address::factory()->create();

    $this->get(route('addresses.index'))->assertRedirect(route('login'));
    $this->post(route('addresses.store'), [])->assertRedirect(route('login'));
    $this->get(route('addresses.edit', $address))->assertRedirect(route('login'));
});

test('address index only shows own addresses', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Address::factory()->create(['user_id' => $user->id]);
    Address::factory()->create(['user_id' => $other->id]);

    $response = $this->actingAs($user)
        ->get(route('addresses.index'))
        ->assertStatus(200);

    $addresses = $response->original->getData()['page']['props']['addresses'];
    expect($addresses)->toHaveCount(1);
});

test('first created address becomes default automatically', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('addresses.store'), [
        'recipient_name' => 'Alice',
        'phone' => '0911111111',
        'address' => '1 Test Rd',
    ])->assertRedirect(route('addresses.index'));

    $address = Address::where('user_id', $user->id)->first();
    expect($address->is_default)->toBeTrue();
});

test('creating a new default address unsets the previous default', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);

    $this->actingAs($user)->post(route('addresses.store'), [
        'recipient_name' => 'Bob',
        'phone' => '0922222222',
        'address' => '2 Test Rd',
        'is_default' => true,
    ])->assertRedirect(route('addresses.index'));

    expect($first->fresh()->is_default)->toBeFalse();
    expect(Address::where('user_id', $user->id)->where('is_default', true)->count())->toBe(1);
});

test('updating an address to default unsets the previous default', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->put(route('addresses.update', $second), [
        'recipient_name' => $second->recipient_name,
        'phone' => $second->phone,
        'address' => $second->address,
        'is_default' => true,
    ])->assertRedirect(route('addresses.index'));

    expect($first->fresh()->is_default)->toBeFalse();
    expect($second->fresh()->is_default)->toBeTrue();
});

test('unchecking the only default address promotes another to default', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->put(route('addresses.update', $first), [
        'recipient_name' => $first->recipient_name,
        'phone' => $first->phone,
        'address' => $first->address,
        'is_default' => false,
    ])->assertRedirect(route('addresses.index'));

    expect($first->fresh()->is_default)->toBeFalse();
    expect($second->fresh()->is_default)->toBeTrue();
});

test('deleting the default address promotes another to default', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete(route('addresses.destroy', $first))
        ->assertRedirect();

    $this->assertDatabaseMissing('addresses', ['id' => $first->id]);
    expect($second->fresh()->is_default)->toBeTrue();
});

test('setDefault switches the default address', function () {
    $user = User::factory()->create();
    $first = Address::factory()->default()->create(['user_id' => $user->id]);
    $second = Address::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('addresses.default', $second))
        ->assertRedirect();

    expect($first->fresh()->is_default)->toBeFalse();
    expect($second->fresh()->is_default)->toBeTrue();
});

test('a user cannot edit or delete another user\'s address', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $address = Address::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)->get(route('addresses.edit', $address))->assertForbidden();
    $this->actingAs($user)->put(route('addresses.update', $address), [
        'recipient_name' => 'Hacker',
        'phone' => '0900000000',
        'address' => 'Nowhere',
    ])->assertForbidden();
    $this->actingAs($user)->delete(route('addresses.destroy', $address))->assertForbidden();
    $this->actingAs($user)->patch(route('addresses.default', $address))->assertForbidden();
});

test('checkout with save_address creates a new saved address', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]);

    $this->actingAs($user)->post(route('checkout.store'), [
        'shipping_name' => 'Test User',
        'shipping_phone' => '0912345678',
        'shipping_address' => '123 Test St',
        'payment_method' => 'simulated',
        'save_address' => true,
    ])->assertRedirect(route('orders.index'));

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'recipient_name' => 'Test User',
        'phone' => '0912345678',
        'address' => '123 Test St',
        'is_default' => true,
    ]);
});

test('checkout without save_address does not create an address', function () {
    $user = User::factory()->create();
    $shop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $cart = Cart::create(['user_id' => $user->id]);
    CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]);

    $this->actingAs($user)->post(route('checkout.store'), [
        'shipping_name' => 'Test User',
        'shipping_phone' => '0912345678',
        'shipping_address' => '123 Test St',
        'payment_method' => 'simulated',
    ])->assertRedirect(route('orders.index'));

    expect(Address::where('user_id', $user->id)->count())->toBe(0);
});
