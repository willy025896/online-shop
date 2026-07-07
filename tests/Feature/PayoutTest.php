<?php

use App\Models\AdminActionLog;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\Payout;
use App\Models\PayoutItem;
use App\Models\Shop;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Support\Facades\Notification;

function makePayoutTestOrder(Shop $shop, array $state = []): Order
{
    return Order::factory()->completed()->create(array_merge([
        'shop_id' => $shop->id,
        'subtotal' => 200,
        'discount' => 0,
        'shipping_fee' => 10,
        'total' => 210,
        'refunded_amount' => 0,
        'completed_at' => now()->subDays(config('returns.window_days') + 1),
    ], $state));
}

test('eligible orders exclude ones still inside the return window', function () {
    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop, ['completed_at' => now()->subDay()]);

    expect(app(PayoutService::class)->eligibleOrders($shop))->toHaveCount(0);
});

test('eligible orders exclude ones with a pending return request', function () {
    $shop = Shop::factory()->create();
    $order = makePayoutTestOrder($shop);
    OrderReturn::factory()->requested()->create(['order_id' => $order->id]);

    expect(app(PayoutService::class)->eligibleOrders($shop))->toHaveCount(0);
});

test('eligible orders exclude ones already paid out', function () {
    $shop = Shop::factory()->create();
    $order = makePayoutTestOrder($shop);

    app(PayoutService::class)->generateForShop($shop);

    expect(app(PayoutService::class)->eligibleOrders($shop))->toHaveCount(0);
    expect(Order::find($order->id)->payoutItem)->not->toBeNull();
});

test('generateForShop computes commission and net amount correctly', function () {
    Notification::fake();

    config(['commission.rate' => 0.05]);
    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop, ['subtotal' => 200, 'discount' => 20, 'shipping_fee' => 10, 'refunded_amount' => 0]);

    $payout = app(PayoutService::class)->generateForShop($shop);

    // gross = 200 - 20 - 0 = 180; commission = 180 * 0.05 = 9; net = 180 - 9 + 10 = 181
    expect((float) $payout->gross_amount)->toBe(180.0);
    expect((float) $payout->commission_amount)->toBe(9.0);
    expect((float) $payout->shipping_amount)->toBe(10.0);
    expect((float) $payout->net_amount)->toBe(181.0);
    expect($payout->items()->count())->toBe(1);
});

test('generateForShop nets out refunded_amount from the commissionable gross', function () {
    config(['commission.rate' => 0.05]);
    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop, ['subtotal' => 200, 'discount' => 0, 'shipping_fee' => 10, 'refunded_amount' => 50]);

    $payout = app(PayoutService::class)->generateForShop($shop);

    // gross = 200 - 0 - 50 = 150; commission = 7.5; net = 150 - 7.5 + 10 = 152.5
    expect((float) $payout->gross_amount)->toBe(150.0);
    expect((float) $payout->commission_amount)->toBe(7.5);
    expect((float) $payout->net_amount)->toBe(152.5);
});

test('generateForShop is idempotent and does not double count already-paid orders', function () {
    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop);

    $first = app(PayoutService::class)->generateForShop($shop);
    $second = app(PayoutService::class)->generateForShop($shop);

    expect($first)->not->toBeNull();
    expect($second)->toBeNull();
    expect(Payout::where('shop_id', $shop->id)->count())->toBe(1);
});

test('generateForShop does not crash or double-pay when an order is claimed by a payout item just before the query runs', function () {
    // Simulates the race a concurrent generateForShop() call would create:
    // order B already has a PayoutItem (as if another request just committed
    // one) by the time this call's eligibility query executes. It must drop
    // out of the result set instead of hitting the unique(order_id) constraint.
    $shop = Shop::factory()->create();
    $orderA = makePayoutTestOrder($shop);
    $orderB = makePayoutTestOrder($shop);

    $otherPayout = Payout::factory()->create(['shop_id' => $shop->id]);
    PayoutItem::factory()->create(['payout_id' => $otherPayout->id, 'order_id' => $orderB->id]);

    $payout = app(PayoutService::class)->generateForShop($shop);

    expect($payout)->not->toBeNull();
    expect($payout->items()->pluck('order_id')->all())->toBe([$orderA->id]);
    expect(PayoutItem::where('order_id', $orderB->id)->count())->toBe(1);
});

test('generateForAllShops only pays out approved shops', function () {
    $approvedShop = Shop::factory()->create(['status' => Shop::STATUS_APPROVED]);
    $suspendedShop = Shop::factory()->create(['status' => Shop::STATUS_SUSPENDED]);
    makePayoutTestOrder($approvedShop);
    makePayoutTestOrder($suspendedShop);

    $payouts = app(PayoutService::class)->generateForAllShops();

    expect($payouts)->toHaveCount(1);
    expect($payouts->first()->shop_id)->toBe($approvedShop->id);
    expect(Payout::where('shop_id', $suspendedShop->id)->exists())->toBeFalse();
});

test('guest and non-admin users cannot access admin payouts routes', function () {
    $seller = User::factory()->seller()->create();

    $this->get(route('admin.payouts.index'))->assertRedirect(route('login'));
    $this->actingAs($seller)->get(route('admin.payouts.index'))->assertForbidden();
    $this->actingAs($seller)->post(route('admin.payouts.run'))->assertForbidden();
});

test('admin can view the payouts index and trigger a payout run, recorded in the audit log', function () {
    $admin = User::factory()->admin()->create();
    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop);

    $this->actingAs($admin)->get(route('admin.payouts.index'))->assertOk();

    $this->actingAs($admin)->post(route('admin.payouts.run'))->assertRedirect();

    expect(Payout::where('shop_id', $shop->id)->count())->toBe(1);
    expect(AdminActionLog::where('action', 'payout.generated')->where('admin_id', $admin->id)->exists())->toBeTrue();
});

test('seller can only see their own shop payouts', function () {
    $shop = Shop::factory()->create();
    $otherShop = Shop::factory()->create();
    $order = makePayoutTestOrder($shop);
    makePayoutTestOrder($otherShop);

    app(PayoutService::class)->generateForShop($shop);
    app(PayoutService::class)->generateForShop($otherShop);

    $this->actingAs($shop->user)
        ->get(route('seller.payouts.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Payouts/Index')
            ->has('payouts.data', 1));
});

test('shop owner is notified when a payout completes', function () {
    Notification::fake();

    $shop = Shop::factory()->create();
    makePayoutTestOrder($shop);

    app(PayoutService::class)->generateForShop($shop);

    Notification::assertSentTo($shop->user, App\Notifications\PayoutCompletedNotification::class);
});
