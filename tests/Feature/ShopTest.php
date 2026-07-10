<?php

use App\Models\Product;
use App\Models\Shop;

test('shops index page can be rendered', function () {
    $this->get(route('shops.index'))->assertStatus(200);
});

test('shop show page can be rendered', function () {
    $shop = Shop::factory()->create();

    $this->get(route('shops.show', $shop->slug))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Shop/Show')
            ->has('shop')
        );
});

test('shop show filters products by min price', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('shops.show', [$shop->slug, 'min_price' => 200]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('shop show filters products by max price', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('shops.show', [$shop->slug, 'max_price' => 200]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('shop show sorts products by name ascending', function () {
    $shop = Shop::factory()->create();
    $zebra = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Zebra']);
    $apple = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Apple']);

    $this->get(route('shops.show', [$shop->slug, 'sort' => 'name']))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $apple->id)
            ->where('products.data.1.id', $zebra->id)
        );
});

test('shop show sorts products by highest rating', function () {
    $shop = Shop::factory()->create();
    $high = Product::factory()->create(['shop_id' => $shop->id, 'reviews_count' => 10, 'rating_sum' => 45]);
    $low = Product::factory()->create(['shop_id' => $shop->id, 'reviews_count' => 10, 'rating_sum' => 20]);

    $this->get(route('shops.show', [$shop->slug, 'sort' => 'rating_desc']))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $high->id)
            ->where('products.data.1.id', $low->id)
        );
});

test('shop show filters products by minimum rating', function () {
    $shop = Shop::factory()->create();
    $high = Product::factory()->create(['shop_id' => $shop->id, 'reviews_count' => 5, 'rating_sum' => 25]);
    Product::factory()->create(['shop_id' => $shop->id, 'reviews_count' => 5, 'rating_sum' => 10]);

    $this->get(route('shops.show', [$shop->slug, 'min_rating' => 4]))
        ->assertInertia(fn ($page) => $page
            ->where('products.total', 1)
            ->where('products.data.0.id', $high->id)
        );
});
