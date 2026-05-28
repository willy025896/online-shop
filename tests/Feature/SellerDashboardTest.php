<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;

function makeSeller(): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => 'approved']);

    return compact('seller', 'shop');
}

function makePaidOrder(Shop $shop, float $total = 100.0, ?Carbon $paidAt = null): Order
{
    $order = Order::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => User::factory()->create()->id,
        'status' => Order::STATUS_PAID,
        'total' => $total,
        'paid_at' => $paidAt ?? now(),
    ]);

    return $order;
}

// ---- Access control ----

test('guest cannot access seller dashboard', function () {
    $this->get(route('seller.dashboard'))->assertRedirect('/login');
});

test('customer cannot access seller dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('seller.dashboard'))
        ->assertForbidden();
});

// ---- Default period (month) ----

test('dashboard returns correct stats shape', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();

    makePaidOrder($shop, 200.0);

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Dashboard')
            ->has('stats.revenue')
            ->has('stats.order_counts')
            ->has('stats.total_orders')
            ->has('chartData')
            ->has('topProducts')
        );
});

// ---- Revenue counting ----

test('revenue counts only paid_at orders and excludes pending', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();

    makePaidOrder($shop, 500.0);

    // pending order (paid_at = null) should NOT count
    Order::factory()->create([
        'shop_id' => $shop->id,
        'user_id' => User::factory()->create()->id,
        'status' => Order::STATUS_PENDING,
        'total' => 999.0,
        'paid_at' => null,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'all']))
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue', fn ($v) => (float) $v === 500.0)
        );
});

test('revenue for today period excludes orders paid yesterday', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();

    makePaidOrder($shop, 100.0, Carbon::now());
    makePaidOrder($shop, 200.0, Carbon::yesterday());

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'today']))
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue', fn ($v) => (float) $v === 100.0)
        );
});

// ---- Revenue growth ----

test('revenue_growth is null for all-time period', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();
    makePaidOrder($shop, 100.0);

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'all']))
        ->assertInertia(fn ($page) => $page->where('stats.revenue_growth', null));
});

test('revenue_growth is 100 when previous period had zero revenue', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();
    makePaidOrder($shop, 50.0, Carbon::now());

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'month']))
        ->assertInertia(fn ($page) => $page->where('stats.revenue_growth', fn ($v) => (float) $v === 100.0));
});

// ---- Order status counts ----

test('order_counts includes all six statuses', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();

    Order::factory()->create(['shop_id' => $shop->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PENDING]);
    Order::factory()->create(['shop_id' => $shop->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_CANCELLED]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'all']))
        ->assertInertia(fn ($page) => $page
            ->has('stats.order_counts.pending')
            ->has('stats.order_counts.paid')
            ->has('stats.order_counts.processing')
            ->has('stats.order_counts.shipped')
            ->has('stats.order_counts.completed')
            ->has('stats.order_counts.cancelled')
            ->where('stats.order_counts.pending', 1)
            ->where('stats.order_counts.cancelled', 1)
        );
});

// ---- Top products ----

test('topProducts orders by qty descending', function () {
    ['seller' => $seller, 'shop' => $shop] = makeSeller();
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $order = makePaidOrder($shop, 300.0);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => 'Top Item',
        'quantity' => 10,
        'unit_price' => 30,
        'subtotal' => 300,
    ]);

    $order2 = makePaidOrder($shop, 20.0);
    OrderItem::factory()->create([
        'order_id' => $order2->id,
        'product_id' => Product::factory()->create(['shop_id' => $shop->id])->id,
        'product_name' => 'Small Item',
        'quantity' => 1,
        'unit_price' => 20,
        'subtotal' => 20,
    ]);

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'all']))
        ->assertInertia(fn ($page) => $page
            ->where('topProducts.0.product_name', 'Top Item')
            ->where('topProducts.0.qty', '10')
            ->where('topProducts.1.product_name', 'Small Item')
        );
});

// ---- Widget preferences ----

test('seller can save dashboard widget preferences', function () {
    ['seller' => $seller] = makeSeller();

    $this->actingAs($seller)
        ->patch(route('seller.preferences.update'), [
            'dashboard_widgets' => [
                'revenue' => true,
                'order_status' => false,
                'top_products' => true,
                'revenue_chart' => false,
            ],
        ])
        ->assertRedirect();

    $prefs = $seller->fresh()->preferences;
    expect($prefs['dashboard_widgets']['order_status'])->toBeFalse();
    expect($prefs['dashboard_widgets']['revenue_chart'])->toBeFalse();
    expect($prefs['dashboard_widgets']['revenue'])->toBeTrue();
});

test('preferences are scoped per user', function () {
    ['seller' => $seller1] = makeSeller();
    ['seller' => $seller2] = makeSeller();

    $this->actingAs($seller1)
        ->patch(route('seller.preferences.update'), [
            'dashboard_widgets' => ['revenue' => false, 'order_status' => true, 'top_products' => true, 'revenue_chart' => true],
        ]);

    expect($seller2->fresh()->preferences)->toBeNull();
});

test('invalid period falls back to month', function () {
    ['seller' => $seller] = makeSeller();

    $this->actingAs($seller)
        ->get(route('seller.dashboard', ['period' => 'invalid']))
        ->assertInertia(fn ($page) => $page->where('period', 'month'));
});
