<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Services\RecommendationService;

beforeEach(function () {
    $this->service = app(RecommendationService::class);
});

test('frequently-bought-together products are ranked first', function () {
    $shop = Shop::factory()->create();
    $target = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $coBought = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $unrelated = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    // $coBought shares two orders with $target; $unrelated never co-occurs.
    makeOrderWithItems($shop, [$target, $coBought]);
    makeOrderWithItems($shop, [$target, $coBought]);
    makeOrderWithItems($shop, [$unrelated]);

    $related = $this->service->relatedTo($target, 4);

    expect($related->first()->id)->toBe($coBought->id);
});

test('co-occurrence ignores cancelled orders', function () {
    $shop = Shop::factory()->create();
    $target = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);
    $coBought = Product::factory()->create(['shop_id' => $shop->id, 'stock' => 10]);

    makeOrderWithItems($shop, [$target, $coBought], Order::STATUS_CANCELLED);

    $related = $this->service->relatedTo($target, 4);

    // The only signal was a cancelled order, so it must not count as bought-together.
    // $coBought may still surface via the same-shop fallback, but never via co-occurrence.
    expect($related)->toHaveCount(1)
        ->and($related->first()->id)->toBe($coBought->id);
});

test('falls back to same category when co-occurrence is insufficient', function () {
    $category = Category::factory()->create();
    $shopA = Shop::factory()->create();
    $shopB = Shop::factory()->create();

    $target = Product::factory()->create(['shop_id' => $shopA->id, 'category_id' => $category->id, 'stock' => 10]);
    $sameCategory = Product::factory()->create(['shop_id' => $shopB->id, 'category_id' => $category->id, 'stock' => 10]);

    $related = $this->service->relatedTo($target, 4);

    expect($related->pluck('id'))->toContain($sameCategory->id);
});

test('falls back to same shop as a last resort', function () {
    $shop = Shop::factory()->create();
    $target = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => null, 'stock' => 10]);
    $sibling = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => null, 'stock' => 10]);

    $related = $this->service->relatedTo($target, 4);

    expect($related->pluck('id'))->toContain($sibling->id);
});

test('excludes the source product, duplicates, inactive and out-of-stock products', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();

    $target = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category->id, 'stock' => 10]);
    $coBought = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category->id, 'stock' => 10]);
    $inactive = Product::factory()->inactive()->create(['shop_id' => $shop->id, 'category_id' => $category->id, 'stock' => 10]);
    $outOfStock = Product::factory()->outOfStock()->create(['shop_id' => $shop->id, 'category_id' => $category->id]);

    // All of these co-occur with $target in one order.
    makeOrderWithItems($shop, [$target, $coBought, $inactive, $outOfStock]);

    $related = $this->service->relatedTo($target, 4);
    $ids = $related->pluck('id');

    expect($ids)->not->toContain($target->id)
        ->and($ids)->not->toContain($inactive->id)
        ->and($ids)->not->toContain($outOfStock->id)
        ->and($ids->count())->toBe($ids->unique()->count())
        ->and($ids)->toContain($coBought->id);
});

test('never returns more than the requested limit', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $target = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category->id, 'stock' => 10]);

    Product::factory(10)->create(['shop_id' => $shop->id, 'category_id' => $category->id, 'stock' => 10]);

    expect($this->service->relatedTo($target, 4))->toHaveCount(4);
});
