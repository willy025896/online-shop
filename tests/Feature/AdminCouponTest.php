<?php

use App\Models\AdminActionLog;
use App\Models\Coupon;
use App\Models\Shop;
use App\Models\User;

test('admin can view the platform coupons list', function () {
    $admin = User::factory()->admin()->create();
    Coupon::factory(2)->create(['shop_id' => null]);
    Coupon::factory()->create(['shop_id' => Shop::factory()->create()->id]); // seller coupon, excluded

    $this->actingAs($admin)
        ->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Coupons/Index')
            ->has('coupons.data', 2)
        );
});

test('admin can create a platform-wide coupon', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.coupons.store'), [
        'code' => 'platform10',
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 10,
        'is_active' => true,
    ])->assertRedirect(route('admin.coupons.index'));

    $coupon = Coupon::first();
    expect($coupon->shop_id)->toBeNull();
    expect($coupon->code)->toBe('PLATFORM10');
    expect(AdminActionLog::where('action', 'coupon.created')->count())->toBe(1);
});

test('admin can update a platform coupon', function () {
    $admin = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create(['shop_id' => null, 'value' => 10]);

    $this->actingAs($admin)->put(route('admin.coupons.update', $coupon), [
        'code' => $coupon->code,
        'type' => Coupon::TYPE_PERCENTAGE,
        'value' => 20,
        'is_active' => true,
    ])->assertRedirect();

    expect((float) $coupon->fresh()->value)->toBe(20.0);
    expect(AdminActionLog::where('action', 'coupon.updated')->count())->toBe(1);
});

test('admin can delete a platform coupon', function () {
    $admin = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create(['shop_id' => null]);

    $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon))
        ->assertRedirect(route('admin.coupons.index'));

    expect(Coupon::find($coupon->id))->toBeNull();
    expect(AdminActionLog::where('action', 'coupon.deleted')->count())->toBe(1);
});

test('admin coupon routes 404 on a shop-owned coupon', function () {
    $admin = User::factory()->admin()->create();
    $coupon = Coupon::factory()->create(['shop_id' => Shop::factory()->create()->id]);

    $this->actingAs($admin)->get(route('admin.coupons.edit', $coupon))->assertNotFound();
    $this->actingAs($admin)->put(route('admin.coupons.update', $coupon), [
        'code' => $coupon->code,
        'type' => Coupon::TYPE_FIXED,
        'value' => 5,
    ])->assertNotFound();
    $this->actingAs($admin)->delete(route('admin.coupons.destroy', $coupon))->assertNotFound();
});

test('seller cannot access admin coupon routes', function () {
    $seller = User::factory()->seller()->create();

    $this->actingAs($seller)
        ->get(route('admin.coupons.index'))
        ->assertForbidden();
});
