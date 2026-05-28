<?php

use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\OrderCancellationRequestedNotification;
use App\Notifications\OrderCancellationRespondedNotification;
use App\Notifications\OrderCancelledBySellerNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Notifications\ShopStatusChangedNotification;
use Illuminate\Support\Facades\Notification;

function makeOrderForNotificationTest(array $orderState = [], int $stock = 5, int $qty = 2): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'stock' => $stock]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(array_merge(['user_id' => $buyer->id, 'shop_id' => $shop->id], $orderState));
    $order->items()->create([
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => $qty,
        'unit_price' => 100,
        'subtotal' => 100 * $qty,
    ]);

    return compact('seller', 'shop', 'product', 'buyer', 'order');
}

// ---- Trigger tests ----

test('paying an order notifies the seller and the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'pending']);

    app(App\Services\PaymentService::class)->simulatePayment($order);

    Notification::assertSentTo($seller, OrderPaidNotification::class);
    Notification::assertSentTo($buyer, OrderStatusChangedNotification::class);
});

test('shipping an order notifies the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'paid']);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => 'shipped'])
        ->assertRedirect();

    Notification::assertSentTo($buyer, OrderStatusChangedNotification::class);
});

test('transitioning to processing does NOT notify the buyer (whitelist filter)', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'paid']);

    $this->actingAs($seller)
        ->patch(route('seller.orders.status', $order), ['status' => 'processing'])
        ->assertRedirect();

    Notification::assertNotSentTo($buyer, OrderStatusChangedNotification::class);
});

test('buyer requesting cancellation notifies the seller', function () {
    Notification::fake();

    ['seller' => $seller, 'order' => $order] = makeOrderForNotificationTest(['status' => 'processing']);

    app(App\Services\OrderService::class)->requestCancellation($order, 'Too slow');

    Notification::assertSentTo($seller, OrderCancellationRequestedNotification::class);
});

test('seller approving a cancellation notifies the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'processing']);
    $cancellation = OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    app(App\Services\OrderService::class)->approveCancellation($cancellation, $seller);

    Notification::assertSentTo($buyer, OrderCancellationRespondedNotification::class);
});

test('seller rejecting a cancellation notifies the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'processing']);
    $cancellation = OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);

    app(App\Services\OrderService::class)->rejectCancellation($cancellation, $seller, 'Already shipped');

    Notification::assertSentTo($buyer, OrderCancellationRespondedNotification::class);
});

test('seller directly cancelling notifies the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'processing']);

    app(App\Services\OrderService::class)->cancelBySeller($order, $seller, 'Out of stock');

    Notification::assertSentTo($buyer, OrderCancelledBySellerNotification::class);
});

test('cancellation paths do NOT also fire the generic status-changed notification (no double-notify)', function () {
    Notification::fake();

    // Path 1: buyer direct cancel (pending → cancelled) — buyer must not self-notify
    ['buyer' => $buyer1, 'order' => $order1] = makeOrderForNotificationTest(['status' => 'pending']);
    app(App\Services\OrderService::class)->directCancelByBuyer($order1, 'changed my mind');

    // Path 2: seller direct cancel (processing → cancelled)
    ['seller' => $seller2, 'buyer' => $buyer2, 'order' => $order2] = makeOrderForNotificationTest(['status' => 'processing']);
    app(App\Services\OrderService::class)->cancelBySeller($order2, $seller2, 'out of stock');

    // Path 3: seller approves a cancellation request (processing → cancelled)
    ['seller' => $seller3, 'buyer' => $buyer3, 'order' => $order3] = makeOrderForNotificationTest(['status' => 'processing']);
    $cancellation = OrderCancellation::factory()->requested()->create(['order_id' => $order3->id]);
    app(App\Services\OrderService::class)->approveCancellation($cancellation, $seller3);

    Notification::assertNotSentTo($buyer1, OrderStatusChangedNotification::class);
    Notification::assertNotSentTo($buyer2, OrderStatusChangedNotification::class);
    Notification::assertNotSentTo($buyer3, OrderStatusChangedNotification::class);
});

test('shop approval notifies the seller', function () {
    Notification::fake();

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id, 'status' => Shop::STATUS_PENDING]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.shops.status', $shop), ['status' => Shop::STATUS_APPROVED])
        ->assertRedirect();

    Notification::assertSentTo($seller, ShopStatusChangedNotification::class);
});

// ---- API tests ----

test('user can list their notifications', function () {
    $user = User::factory()->create();
    $user->notify(new OrderPaidNotification(Order::factory()->create()));

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Notifications/Index')
            ->has('notifications.data', 1)
        );
});

test('user can mark a single notification as read', function () {
    $user = User::factory()->create();
    $user->notify(new OrderPaidNotification(Order::factory()->create()));
    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect();

    expect($user->notifications()->first()->read_at)->not->toBeNull();
});

test('user can mark all notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new OrderPaidNotification(Order::factory()->create()));
    $user->notify(new OrderPaidNotification(Order::factory()->create()));

    $this->actingAs($user)
        ->post(route('notifications.read_all'))
        ->assertRedirect();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('user can delete their notification', function () {
    $user = User::factory()->create();
    $user->notify(new OrderPaidNotification(Order::factory()->create()));
    $notification = $user->notifications()->first();

    $this->actingAs($user)
        ->delete(route('notifications.destroy', $notification->id))
        ->assertRedirect();

    expect($user->notifications()->count())->toBe(0);
});

test('user cannot mark another users notification', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $owner->notify(new OrderPaidNotification(Order::factory()->create()));
    $notification = $owner->notifications()->first();

    $this->actingAs($intruder)
        ->post(route('notifications.read', $notification->id))
        ->assertNotFound();
});

test('Inertia share exposes unread count and recent notifications', function () {
    $user = User::factory()->create();
    $user->notify(new OrderPaidNotification(Order::factory()->create()));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('unreadNotificationCount', 1)
            ->has('recentNotifications', 1)
        );
});
