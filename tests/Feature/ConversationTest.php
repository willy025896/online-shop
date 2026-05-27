<?php

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

test('guest cannot access messages page', function () {
    $this->get(route('messages.index'))->assertRedirect('/login');
});

test('authenticated user can view messages index', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('messages.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Messages/Index'));
});

test('buyer can start conversation from order', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);

    $this->actingAs($buyer)
        ->post(route('orders.conversation', $order))
        ->assertRedirect();

    $this->assertDatabaseHas('conversations', [
        'order_id' => $order->id,
        'buyer_id' => $buyer->id,
        'seller_user_id' => $seller->id,
    ]);
});

test('starting conversation twice returns same conversation', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);

    $this->actingAs($buyer)->post(route('orders.conversation', $order));
    $this->actingAs($buyer)->post(route('orders.conversation', $order));

    expect(Conversation::where('order_id', $order->id)->count())->toBe(1);
});

test('non-participant cannot view conversation', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $outsider = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    $this->actingAs($outsider)
        ->get(route('messages.show', $conv))
        ->assertStatus(403);
});

test('participant can send text message', function () {
    Event::fake();

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    $this->actingAs($buyer)
        ->post(route('messages.store', $conv), ['body' => 'Hello seller'])
        ->assertRedirect();

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conv->id,
        'sender_id' => $buyer->id,
        'body' => 'Hello seller',
    ]);

    Event::assertDispatched(MessageSent::class);
});

test('cannot send empty message', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    $this->actingAs($buyer)
        ->post(route('messages.store', $conv), ['body' => ''])
        ->assertSessionHasErrors('body');
});

test('participant can upload image', function () {
    Storage::fake('public');
    Event::fake();

    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    $file = UploadedFile::fake()->image('test.jpg');

    $this->actingAs($buyer)
        ->post(route('messages.store', $conv), ['image' => $file])
        ->assertRedirect();

    $message = Message::first();
    expect($message)->not->toBeNull();
    expect($message->image_path)->toContain("messages/{$conv->id}/");
    Storage::disk('public')->assertExists($message->image_path);
});

test('viewing conversation marks opponent messages as read', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    Message::factory()->count(3)->create([
        'conversation_id' => $conv->id,
        'sender_id' => $seller->id,
        'read_at' => null,
    ]);

    $this->actingAs($buyer)->get(route('messages.show', $conv));

    expect(Message::where('conversation_id', $conv->id)->whereNull('read_at')->count())->toBe(0);
});

test('unread count only counts opponent messages', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);
    $conv = Conversation::factory()->create([
        'order_id' => $order->id, 'buyer_id' => $buyer->id, 'seller_user_id' => $seller->id,
    ]);

    Message::factory()->count(2)->create([
        'conversation_id' => $conv->id, 'sender_id' => $seller->id, 'read_at' => null,
    ]);
    Message::factory()->create([
        'conversation_id' => $conv->id, 'sender_id' => $buyer->id, 'read_at' => null,
    ]);

    expect($conv->unreadCountFor($buyer))->toBe(2);
    expect($conv->unreadCountFor($seller))->toBe(1);
});
