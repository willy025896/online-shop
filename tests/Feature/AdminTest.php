<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Carbon\Carbon;

test('guest cannot access admin pages', function () {
    $this->get(route('admin.dashboard'))->assertRedirect('/login');
    $this->get(route('admin.users.index'))->assertRedirect('/login');
});

test('customer cannot access admin pages', function () {
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('seller cannot access admin pages', function () {
    $user = User::factory()->seller()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin can access dashboard', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Dashboard')
            ->has('stats.total_users')
            ->has('stats.total_revenue')
            ->has('stats.revenue')
            ->has('stats.order_counts.pending')
            ->has('chartData')
            ->has('topShops')
            ->where('period', 'month')
        );
});

test('admin dashboard revenue is platform-wide and period-scoped', function () {
    $admin = User::factory()->admin()->create();
    $shopA = Shop::factory()->create(['status' => Shop::STATUS_APPROVED]);
    $shopB = Shop::factory()->create(['status' => Shop::STATUS_APPROVED]);

    // Two shops paid today, one pending (excluded), one paid yesterday.
    Order::factory()->create(['shop_id' => $shopA->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PAID, 'total' => 300.0, 'paid_at' => Carbon::now()]);
    Order::factory()->create(['shop_id' => $shopB->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PAID, 'total' => 200.0, 'paid_at' => Carbon::now()]);
    Order::factory()->create(['shop_id' => $shopA->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PENDING, 'total' => 999.0, 'paid_at' => null]);
    Order::factory()->create(['shop_id' => $shopB->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PAID, 'total' => 777.0, 'paid_at' => Carbon::yesterday()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['period' => 'today']))
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue', fn ($v) => (float) $v === 500.0)
        );
});

test('admin dashboard top shops are ordered by revenue descending', function () {
    $admin = User::factory()->admin()->create();
    $big = Shop::factory()->create(['name' => 'Big Shop', 'status' => Shop::STATUS_APPROVED]);
    $small = Shop::factory()->create(['name' => 'Small Shop', 'status' => Shop::STATUS_APPROVED]);

    Order::factory()->create(['shop_id' => $big->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PAID, 'total' => 900.0, 'paid_at' => Carbon::now()]);
    Order::factory()->create(['shop_id' => $small->id, 'user_id' => User::factory()->create()->id, 'status' => Order::STATUS_PAID, 'total' => 50.0, 'paid_at' => Carbon::now()]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['period' => 'all']))
        ->assertInertia(fn ($page) => $page
            ->where('topShops.0.shop_name', 'Big Shop')
            ->where('topShops.1.shop_name', 'Small Shop')
        );
});

test('admin dashboard invalid period falls back to month', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard', ['period' => 'bogus']))
        ->assertInertia(fn ($page) => $page->where('period', 'month'));
});

test('admin can view users list', function () {
    $admin = User::factory()->admin()->create();
    User::factory(5)->create();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Users/Index')
            ->has('users.data', 6) // 5 + admin
        );
});

test('admin can update user role', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['role' => 'customer']);

    $this->actingAs($admin)
        ->patch(route('admin.users.role', $user), ['role' => 'seller'])
        ->assertRedirect();

    expect($user->fresh()->role)->toBe('seller');
});

test('admin can view shops list', function () {
    $admin = User::factory()->admin()->create();
    Shop::factory(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.shops.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Shops/Index')
            ->has('shops.data', 3)
        );
});

test('admin can approve a shop', function () {
    $admin = User::factory()->admin()->create();
    $shop = Shop::factory()->pending()->create();

    $this->actingAs($admin)
        ->patch(route('admin.shops.status', $shop), ['status' => Shop::STATUS_APPROVED])
        ->assertRedirect();

    expect($shop->fresh()->status)->toBe(Shop::STATUS_APPROVED);
    expect($shop->fresh()->approved_at)->not->toBeNull();
});

test('admin can suspend a shop', function () {
    $admin = User::factory()->admin()->create();
    $shop = Shop::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.shops.status', $shop), ['status' => Shop::STATUS_SUSPENDED])
        ->assertRedirect();

    expect($shop->fresh()->status)->toBe(Shop::STATUS_SUSPENDED);
});

test('admin can view categories', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Admin/Categories/Index'));
});

test('admin can create a category', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.categories.store'), [
            'name' => 'New Category',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(Category::where('name', 'New Category')->exists())->toBeTrue();
});

test('admin can update a category', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.categories.update', $category), [
            'name' => 'Updated Category',
            'parent_id' => null,
            'sort_order' => 5,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('Updated Category');
});

test('admin can delete a category without children', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect();

    expect(Category::find($category->id))->toBeNull();
});

test('admin cannot delete a category with children', function () {
    $admin = User::factory()->admin()->create();
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $this->actingAs($admin)
        ->delete(route('admin.categories.destroy', $parent))
        ->assertRedirect();

    expect(Category::find($parent->id))->not->toBeNull();
});

test('admin can view orders list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Admin/Orders/Index'));
});

test('admin can view products list', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.products.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Admin/Products/Index'));
});
