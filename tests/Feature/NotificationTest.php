<?php

use App\Models\Message;
use App\Models\Order;
use App\Models\OrderCancellation;
use App\Models\OrderReturn;
use App\Models\Payout;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\OrderCancellationRequestedNotification;
use App\Notifications\OrderCancellationRespondedNotification;
use App\Notifications\OrderCancelledBySellerNotification;
use App\Notifications\OrderPaidNotification;
use App\Notifications\OrderReturnRequestedNotification;
use App\Notifications\OrderReturnRespondedNotification;
use App\Notifications\OrderStatusChangedNotification;
use App\Notifications\PayoutCompletedNotification;
use App\Notifications\ReviewCoolingResetNotification;
use App\Notifications\ReviewCoolingStartedNotification;
use App\Notifications\ReviewReleasedNotification;
use App\Notifications\SellerReplyNotification;
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

/** @return array<int, \Illuminate\Notifications\Notification> */
function mailEnabledNotifications(Order $order, OrderCancellation $cancellation, OrderReturn $orderReturn, Payout $payout): array
{
    return [
        new OrderPaidNotification($order),
        new OrderStatusChangedNotification($order, Order::STATUS_SHIPPED),
        new OrderCancellationRequestedNotification($order),
        new OrderCancellationRespondedNotification($cancellation),
        new OrderCancelledBySellerNotification($order),
        new OrderReturnRequestedNotification($order),
        new OrderReturnRespondedNotification($orderReturn),
        new PayoutCompletedNotification($payout),
        new ShopStatusChangedNotification($order->shop),
    ];
}

// ---- Trigger tests ----

test('paying an order notifies the seller and the buyer', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest(['status' => 'pending']);

    app(App\Services\PaymentService::class)->markAsPaid($order);

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

// ---- Mail channel tests ----

test('order-lifecycle-critical notifications include the mail channel', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest();
    $cancellation = OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);
    $orderReturn = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $payout = Payout::factory()->create(['shop_id' => $order->shop_id]);

    foreach (mailEnabledNotifications($order, $cancellation, $orderReturn, $payout) as $notification) {
        expect($notification->via($buyer))->toContain('mail');
    }
});

test('chat and review-flow notifications do NOT include the mail channel', function () {
    ['order' => $order] = makeOrderForNotificationTest();
    $message = Message::factory()->create();
    $productReview = ProductReview::factory()->create();

    $notMailable = [
        new NewMessageNotification($message),
        new ReviewCoolingStartedNotification($order, now()->addDay()),
        new ReviewCoolingResetNotification($order),
        new ReviewReleasedNotification($order, null),
        new SellerReplyNotification($productReview),
    ];

    foreach ($notMailable as $notification) {
        expect($notification->via($order->user))->not->toContain('mail');
    }
});

test('OrderPaidNotification renders a mail message from the same payload as the bell', function () {
    ['seller' => $seller, 'order' => $order] = makeOrderForNotificationTest();

    $mail = (new OrderPaidNotification($order))->toMail($seller);

    expect($mail->subject)->toBe(__('notifications.order.paid.title'));
    expect($mail->introLines)->toContain(__('notifications.order.paid.body', ['number' => $order->order_number]));
    expect($mail->actionText)->toBe(__('notifications.mail.view_details'));
    expect($mail->actionUrl)->toBe(route('seller.orders.show', $order));
});

test('OrderCancellationRespondedNotification mail reflects the approved/rejected outcome', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest();
    $cancellation = OrderCancellation::factory()->approved()->create(['order_id' => $order->id]);

    $mail = (new OrderCancellationRespondedNotification($cancellation))->toMail($buyer);

    expect($mail->subject)->toBe(__('notifications.order.cancellation_approved.title'));
    expect($mail->actionUrl)->toBe(route('orders.show', $order));
});

// ---- Locale persistence tests ----

test('preferredLocale returns the persisted locale column', function () {
    $user = User::factory()->create(['locale' => 'zh_TW']);

    expect($user->preferredLocale())->toBe('zh_TW');
    expect(User::factory()->create(['locale' => null])->preferredLocale())->toBeNull();
});

test('switching locale persists it onto the authenticated user', function () {
    $user = User::factory()->create(['locale' => null]);

    $this->actingAs($user)
        ->post(route('locale.store'), ['locale' => 'zh_TW'])
        ->assertRedirect();

    expect($user->fresh()->locale)->toBe('zh_TW');
});

test('switching locale as a guest only writes to the session', function () {
    $this->post(route('locale.store'), ['locale' => 'zh_TW'])
        ->assertRedirect();

    expect(session('locale'))->toBe('zh_TW');
});

test('User implements HasLocalePreference so Laravel wraps queued notification sends in the recipient locale', function () {
    // This is the actual wiring a queued send depends on (NotificationSender::sendNow()
    // only calls preferredLocale() and wraps the whole send in App::setLocale(...) for
    // notifiables implementing this contract) — asserting the contract, not
    // re-testing Laravel's own withLocale()/preferredLocale() machinery.
    expect(User::factory()->create())->toBeInstanceOf(\Illuminate\Contracts\Translation\HasLocalePreference::class);
});

test('the 9 mail-enabled notifications implement ShouldQueue so mail sends never block the triggering request/transaction', function () {
    ['order' => $order] = makeOrderForNotificationTest();
    $cancellation = OrderCancellation::factory()->requested()->create(['order_id' => $order->id]);
    $orderReturn = OrderReturn::factory()->requested()->create(['order_id' => $order->id]);
    $payout = Payout::factory()->create(['shop_id' => $order->shop_id]);

    foreach (mailEnabledNotifications($order, $cancellation, $orderReturn, $payout) as $notification) {
        expect($notification)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    }
});

test('an approved return mail includes the refund amount, not just the in-app meta', function () {
    ['buyer' => $buyer, 'order' => $order] = makeOrderForNotificationTest();
    $orderReturn = OrderReturn::factory()->approved()->create(['order_id' => $order->id, 'refund_amount' => 150]);

    $notification = new OrderReturnRespondedNotification($orderReturn);
    $data = $notification->toArray($buyer);
    $mail = $notification->toMail($buyer);

    expect($data['body'])->toContain('150.00');
    expect($mail->introLines)->toContain($data['body']);
});

test('SetLocale falls back to the authenticated users persisted locale when the session has none', function () {
    $user = User::factory()->create(['locale' => 'zh_TW']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('locale', 'zh_TW'));

    expect(session('locale'))->toBe('zh_TW');
});

test('registering a new user seeds locale from the current session', function () {
    session(['locale' => 'zh_TW']);

    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect();

    expect(User::where('email', 'newuser@example.com')->first()->locale)->toBe('zh_TW');
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
