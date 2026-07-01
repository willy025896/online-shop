<?php

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Services\CouponService;

beforeEach(function () {
    $this->service = app(CouponService::class);
    $this->shop = Shop::factory()->create();
    $this->user = User::factory()->create();
});

// ---- discountFor ----

test('percentage discount is a percent of subtotal', function () {
    $coupon = Coupon::factory()->percentage(10)->make();

    expect($this->service->discountFor($coupon, 200.0))->toBe(20.0);
});

test('percentage discount respects max_discount cap', function () {
    $coupon = Coupon::factory()->percentage(50)->make(['max_discount' => 30]);

    expect($this->service->discountFor($coupon, 200.0))->toBe(30.0);
});

test('fixed discount never exceeds the subtotal', function () {
    $coupon = Coupon::factory()->fixed(150)->make();

    expect($this->service->discountFor($coupon, 100.0))->toBe(100.0);
});

// ---- validate: failure reasons ----

test('validate rejects unknown code', function () {
    $this->service->validate('NOPE', $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'not_found');

test('validate rejects inactive coupon', function () {
    $coupon = Coupon::factory()->inactive()->create(['shop_id' => $this->shop->id]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'inactive');

test('validate rejects expired coupon', function () {
    $coupon = Coupon::factory()->expired()->create(['shop_id' => $this->shop->id]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'expired');

test('validate rejects coupon belonging to another shop', function () {
    $coupon = Coupon::factory()->create(['shop_id' => Shop::factory()->create()->id]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'wrong_shop');

test('validate rejects subtotal below min_spend', function () {
    $coupon = Coupon::factory()->create(['shop_id' => $this->shop->id, 'min_spend' => 500]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'min_spend');

test('validate rejects when total usage exhausted', function () {
    $coupon = Coupon::factory()->create([
        'shop_id' => $this->shop->id, 'usage_limit' => 1, 'used_count' => 1,
    ]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'usage_exhausted');

test('validate rejects when per-user limit reached', function () {
    $coupon = Coupon::factory()->create(['shop_id' => $this->shop->id, 'per_user_limit' => 1]);
    CouponRedemption::create([
        'coupon_id' => $coupon->id,
        'user_id' => $this->user->id,
        'order_id' => Order::factory()->create(['user_id' => $this->user->id, 'shop_id' => $this->shop->id])->id,
        'discount_amount' => 5,
    ]);

    $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);
})->throws(CouponException::class, 'per_user_exhausted');

test('validate is case-insensitive and returns the coupon', function () {
    $coupon = Coupon::factory()->create(['shop_id' => $this->shop->id, 'code' => 'SAVE10']);

    $result = $this->service->validate('save10', $this->shop->id, 100, $this->user->id);

    expect($result->id)->toBe($coupon->id);
});

test('platform-wide coupon (null shop_id) matches any shop', function () {
    $coupon = Coupon::factory()->create(['shop_id' => null]);

    $result = $this->service->validate($coupon->code, $this->shop->id, 100, $this->user->id);

    expect($result->id)->toBe($coupon->id);
});

// ---- redeem ----

test('redeem increments used_count and records a redemption', function () {
    $coupon = Coupon::factory()->create(['shop_id' => $this->shop->id, 'usage_limit' => 5]);
    $order = Order::factory()->create(['user_id' => $this->user->id, 'shop_id' => $this->shop->id]);

    DB::transaction(fn () => $this->service->redeem($coupon, $this->user->id, $order, 20.0));

    expect($coupon->fresh()->used_count)->toBe(1);
    expect(CouponRedemption::where('coupon_id', $coupon->id)->where('order_id', $order->id)->exists())->toBeTrue();
});

test('redeem throws when the coupon was exhausted under the lock', function () {
    $coupon = Coupon::factory()->create(['shop_id' => $this->shop->id, 'usage_limit' => 1, 'used_count' => 1]);
    $order = Order::factory()->create(['user_id' => $this->user->id, 'shop_id' => $this->shop->id]);

    DB::transaction(fn () => $this->service->redeem($coupon, $this->user->id, $order, 20.0));
})->throws(CouponException::class, 'usage_exhausted');
