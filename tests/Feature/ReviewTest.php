<?php

use App\Models\BuyerReview;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Shop;
use App\Models\User;
use App\Notifications\ReviewCoolingStartedNotification;
use App\Notifications\ReviewReleasedNotification;
use App\Notifications\SellerReplyNotification;
use App\Services\ReviewService;
use Illuminate\Support\Facades\Notification;

function makeReviewScenario(): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $buyer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'status' => Order::STATUS_COMPLETED,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_name' => $product->name,
        'quantity' => 1,
        'unit_price' => 100,
        'subtotal' => 100,
    ]);

    return compact('seller', 'shop', 'product', 'buyer', 'order', 'orderItem');
}

// ---- Review window ----

test('buyer can write a product review on a completed order', function () {
    ['buyer' => $buyer, 'order' => $order, 'orderItem' => $orderItem] = makeReviewScenario();

    $service = app(ReviewService::class);
    $review = $service->submitProductReview($orderItem, ['rating' => 5, 'comment' => 'Great!']);

    expect($review->rating)->toBe(5)
        ->and($review->user_id)->toBe($buyer->id);
});

test('buyer cannot write a review after order review window is closed', function () {
    ['order' => $order, 'orderItem' => $orderItem] = makeReviewScenario();

    $order->update(['review_released_at' => now()]);

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

    app(ReviewService::class)->submitProductReview($orderItem, ['rating' => 4]);
});

test('buyer cannot review the same order item twice', function () {
    ['orderItem' => $orderItem] = makeReviewScenario();

    $service = app(ReviewService::class);
    $service->submitProductReview($orderItem, ['rating' => 5]);

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $service->submitProductReview($orderItem, ['rating' => 3]);
});

// ---- Cooling period ----

test('cooling period starts when both sides have submitted reviews', function () {
    Notification::fake();

    ['seller' => $seller, 'shop' => $shop, 'buyer' => $buyer, 'order' => $order, 'orderItem' => $orderItem] = makeReviewScenario();

    $service = app(ReviewService::class);
    $service->submitProductReview($orderItem, ['rating' => 5]);
    $service->submitBuyerReview($order, $shop, ['rating' => 4]);

    $order->refresh();
    expect($order->review_cooling_until)->not->toBeNull()
        ->and($order->review_cooling_until->isFuture())->toBeTrue();

    Notification::assertSentTo($buyer, ReviewCoolingStartedNotification::class);
    Notification::assertSentTo($seller, ReviewCoolingStartedNotification::class);
});

test('cooling period does not start if only one side has reviewed', function () {
    ['orderItem' => $orderItem, 'order' => $order] = makeReviewScenario();

    app(ReviewService::class)->submitProductReview($orderItem, ['rating' => 5]);

    $order->refresh();
    expect($order->review_cooling_until)->toBeNull();
});

// ---- Release ----

test('releasing an order updates product and shop aggregates', function () {
    ['shop' => $shop, 'product' => $product, 'order' => $order, 'orderItem' => $orderItem] = makeReviewScenario();

    $service = app(ReviewService::class);
    ProductReview::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'user_id' => $order->user_id,
        'order_item_id' => $orderItem->id,
        'rating' => 4,
        'status' => ProductReview::STATUS_PUBLISHED,
    ]);

    $service->releaseOrder($order);

    $product->refresh();
    $shop->refresh();

    expect($product->reviews_count)->toBe(1)
        ->and($product->rating_sum)->toBe(4)
        ->and($shop->reviews_count)->toBe(1)
        ->and($shop->rating_sum)->toBe(4);
});

test('releasing an order updates buyer aggregate', function () {
    ['buyer' => $buyer, 'shop' => $shop, 'order' => $order] = makeReviewScenario();

    BuyerReview::factory()->create([
        'user_id' => $buyer->id,
        'shop_id' => $shop->id,
        'order_id' => $order->id,
        'rating' => 5,
        'status' => BuyerReview::STATUS_PUBLISHED,
    ]);

    app(ReviewService::class)->releaseOrder($order);

    $buyer->refresh();
    expect($buyer->buyer_reviews_count)->toBe(1)
        ->and($buyer->buyer_rating_sum)->toBe(5);
});

test('releasing an order notifies both buyer and seller', function () {
    Notification::fake();

    ['seller' => $seller, 'buyer' => $buyer, 'shop' => $shop, 'order' => $order, 'orderItem' => $orderItem, 'product' => $product] = makeReviewScenario();

    ProductReview::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'user_id' => $buyer->id,
        'order_item_id' => $orderItem->id,
        'rating' => 4,
    ]);

    app(ReviewService::class)->releaseOrder($order);

    Notification::assertSentTo($buyer, ReviewReleasedNotification::class);
    Notification::assertSentTo($seller, ReviewReleasedNotification::class);
});

test('review cannot be updated after window is closed', function () {
    ['order' => $order, 'orderItem' => $orderItem, 'product' => $product, 'shop' => $shop, 'buyer' => $buyer] = makeReviewScenario();

    $review = ProductReview::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'user_id' => $buyer->id,
        'order_item_id' => $orderItem->id,
        'rating' => 3,
    ]);

    $order->update(['review_released_at' => now()]);
    $review->load('orderItem.order');

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    app(ReviewService::class)->updateProductReview($review, ['rating' => 5]);
});

// ---- Seller reply ----

test('seller can reply to a product review', function () {
    Notification::fake();

    ['shop' => $shop, 'buyer' => $buyer, 'order' => $order, 'orderItem' => $orderItem, 'product' => $product] = makeReviewScenario();

    $order->update(['review_released_at' => now()]);

    $review = ProductReview::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'user_id' => $buyer->id,
        'order_item_id' => $orderItem->id,
        'rating' => 5,
    ]);

    app(ReviewService::class)->addSellerReply($review, 'Thank you!');

    $review->refresh();
    expect($review->seller_reply)->toBe('Thank you!')
        ->and($review->seller_replied_at)->not->toBeNull();

    Notification::assertSentTo($buyer, SellerReplyNotification::class);
});

test('seller cannot reply twice', function () {
    ['shop' => $shop, 'buyer' => $buyer, 'order' => $order, 'orderItem' => $orderItem, 'product' => $product] = makeReviewScenario();

    $review = ProductReview::factory()->create([
        'product_id' => $product->id,
        'shop_id' => $shop->id,
        'user_id' => $buyer->id,
        'order_item_id' => $orderItem->id,
        'rating' => 5,
        'seller_reply' => 'First reply',
        'seller_replied_at' => now(),
    ]);

    $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    app(ReviewService::class)->addSellerReply($review, 'Second reply');
});

// ---- Product rating sort/filter ----

test('products can be sorted by rating descending', function () {
    $shop = Shop::factory()->create();
    $high = Product::factory()->create(['shop_id' => $shop->id, 'status' => Product::STATUS_ACTIVE, 'reviews_count' => 10, 'rating_sum' => 45]);
    $low = Product::factory()->create(['shop_id' => $shop->id, 'status' => Product::STATUS_ACTIVE, 'reviews_count' => 10, 'rating_sum' => 20]);

    $this->get(route('products.index', ['sort' => 'rating_desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->where('products.data.0.id', $high->id)
        );
});

test('products can be filtered by minimum rating', function () {
    $shop = Shop::factory()->create();
    $high = Product::factory()->create(['shop_id' => $shop->id, 'status' => Product::STATUS_ACTIVE, 'reviews_count' => 5, 'rating_sum' => 25]);
    Product::factory()->create(['shop_id' => $shop->id, 'status' => Product::STATUS_ACTIVE, 'reviews_count' => 5, 'rating_sum' => 10]);

    $this->get(route('products.index', ['min_rating' => 4]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->where('products.total', 1)
            ->where('products.data.0.id', $high->id)
        );
});

// ---- Buyer credit privacy ----

test('seller can view buyer credit for their own orders', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $buyer = User::factory()->create();
    Order::factory()->create(['user_id' => $buyer->id, 'shop_id' => $shop->id]);

    $this->actingAs($seller)
        ->get(route('seller.buyers.show', $buyer))
        ->assertOk();
});

test('seller cannot view buyer credit for orders from other shops', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $otherBuyer = User::factory()->create();

    $this->actingAs($seller)
        ->get(route('seller.buyers.show', $otherBuyer))
        ->assertForbidden();
});
