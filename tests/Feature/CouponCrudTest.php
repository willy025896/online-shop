<?php

use App\Models\Coupon;
use App\Models\Shop;
use App\Models\User;

function couponSeller(): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    return [$seller, $shop];
}

test('seller can view the coupons list', function () {
    [$seller, $shop] = couponSeller();
    Coupon::factory(3)->create(['shop_id' => $shop->id]);

    $this->actingAs($seller)
        ->get(route('seller.coupons.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Coupons/Index')
            ->has('coupons.data', 3)
        );
});

test('seller can create a coupon scoped to their shop', function () {
    [$seller, $shop] = couponSeller();

    $this->actingAs($seller)->post(route('seller.coupons.store'), [
        'code' => 'welcome10',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'min_spend' => 100,
        'is_active' => true,
    ])->assertRedirect(route('seller.coupons.index'));

    $coupon = Coupon::first();
    expect($coupon->shop_id)->toBe($shop->id);
    expect($coupon->code)->toBe('WELCOME10'); // normalized to uppercase
});

test('coupon code must be unique', function () {
    [$seller, $shop] = couponSeller();
    Coupon::factory()->create(['shop_id' => $shop->id, 'code' => 'DUP']);

    $this->actingAs($seller)->post(route('seller.coupons.store'), [
        'code' => 'dup',
        'type' => Coupon::TYPE_FIXED,
        'value' => 50,
    ])->assertSessionHasErrors('code');
});

test('percentage coupon value cannot exceed 100', function () {
    [$seller] = couponSeller();

    $this->actingAs($seller)->post(route('seller.coupons.store'), [
        'code' => 'BIG',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 150,
    ])->assertSessionHasErrors('value');
});

test('expires_at must be after starts_at', function () {
    [$seller] = couponSeller();

    $this->actingAs($seller)->post(route('seller.coupons.store'), [
        'code' => 'WINDOW',
        'type' => Coupon::TYPE_FIXED,
        'value' => 10,
        'starts_at' => now()->addDay()->toDateString(),
        'expires_at' => now()->toDateString(),
    ])->assertSessionHasErrors('expires_at');
});

test('seller can update their own coupon', function () {
    [$seller, $shop] = couponSeller();
    $coupon = Coupon::factory()->create(['shop_id' => $shop->id, 'value' => 10]);

    $this->actingAs($seller)->put(route('seller.coupons.update', $coupon), [
        'code' => $coupon->code,
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 25,
        'is_active' => false,
    ])->assertRedirect();

    expect((float) $coupon->fresh()->value)->toBe(25.0);
    expect($coupon->fresh()->is_active)->toBeFalse();
});

test('seller cannot update another sellers coupon', function () {
    [$seller] = couponSeller();
    $otherShop = Shop::factory()->create(['user_id' => User::factory()->seller()->create()->id]);
    $coupon = Coupon::factory()->create(['shop_id' => $otherShop->id]);

    $this->actingAs($seller)->put(route('seller.coupons.update', $coupon), [
        'code' => $coupon->code,
        'type' => Coupon::TYPE_FIXED,
        'value' => 5,
    ])->assertForbidden();
});

test('seller can delete their own coupon', function () {
    [$seller, $shop] = couponSeller();
    $coupon = Coupon::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($seller)->delete(route('seller.coupons.destroy', $coupon))
        ->assertRedirect(route('seller.coupons.index'));

    expect(Coupon::find($coupon->id))->toBeNull(); // soft-deleted
});

test('a soft-deleted coupon code can be reused', function () {
    [$seller, $shop] = couponSeller();
    $coupon = Coupon::factory()->create(['shop_id' => $shop->id, 'code' => 'REUSE']);
    $coupon->delete(); // soft delete

    $this->actingAs($seller)->post(route('seller.coupons.store'), [
        'code' => 'reuse',
        'type' => Coupon::TYPE_FIXED,
        'value' => 20,
    ])->assertRedirect(route('seller.coupons.index'));

    expect(Coupon::where('code', 'REUSE')->count())->toBe(1); // only the live one
});

test('customer cannot access seller coupons', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('seller.coupons.index'))
        ->assertForbidden();
});
