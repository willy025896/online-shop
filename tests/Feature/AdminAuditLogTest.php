<?php

use App\Models\AdminActionLog;
use App\Models\Category;
use App\Models\Shop;
use App\Models\User;

test('changing a user role writes an audit log entry', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

    $this->actingAs($admin)->patch(route('admin.users.role', $user), [
        'role' => User::ROLE_SELLER,
    ])->assertRedirect();

    $log = AdminActionLog::where('action', 'user.role_updated')->first();
    expect($log)->not->toBeNull();
    expect($log->admin_id)->toBe($admin->id);
    expect($log->subject_id)->toBe($user->id);
    expect($log->changes)->toMatchArray(['from' => User::ROLE_CUSTOMER, 'to' => User::ROLE_SELLER]);
});

test('updating shop status writes an audit log entry', function () {
    $admin = User::factory()->admin()->create();
    $shop = Shop::factory()->pending()->create();

    $this->actingAs($admin)->patch(route('admin.shops.status', $shop), [
        'status' => Shop::STATUS_APPROVED,
    ])->assertRedirect();

    $log = AdminActionLog::where('action', 'shop.status_updated')->first();
    expect($log)->not->toBeNull();
    expect($log->changes)->toMatchArray(['from' => Shop::STATUS_PENDING, 'to' => Shop::STATUS_APPROVED]);
});

test('creating, updating and deleting a category each write an audit log entry', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'name' => 'Gadgets',
        'sort_order' => 1,
        'is_active' => true,
    ])->assertRedirect();

    $category = Category::where('name', 'Gadgets')->firstOrFail();
    expect(AdminActionLog::where('action', 'category.created')->where('subject_id', $category->id)->exists())->toBeTrue();

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => 'Electronics',
        'parent_id' => null,
        'sort_order' => 2,
        'is_active' => true,
    ])->assertRedirect();

    expect(AdminActionLog::where('action', 'category.updated')->where('subject_id', $category->id)->exists())->toBeTrue();

    $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();

    expect(AdminActionLog::where('action', 'category.deleted')->where('subject_id', $category->id)->exists())->toBeTrue();
});

test('audit log list page is admin-only', function () {
    $admin = User::factory()->admin()->create();
    $seller = User::factory()->seller()->create();
    $customer = User::factory()->create();

    AdminActionLog::factory()->count(3)->create(['admin_id' => $admin->id]);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/AuditLogs/Index')
            ->has('logs.data', 3)
        );

    $this->actingAs($seller)->get(route('admin.audit-logs.index'))->assertForbidden();
    $this->actingAs($customer)->get(route('admin.audit-logs.index'))->assertForbidden();
});

test('audit log list can be filtered by action', function () {
    $admin = User::factory()->admin()->create();
    AdminActionLog::factory()->create(['admin_id' => $admin->id, 'action' => 'user.role_updated']);
    AdminActionLog::factory()->create(['admin_id' => $admin->id, 'action' => 'shop.status_updated']);

    $this->actingAs($admin)
        ->get(route('admin.audit-logs.index', ['action' => 'user.role_updated']))
        ->assertInertia(fn ($page) => $page->has('logs.data', 1));
});
