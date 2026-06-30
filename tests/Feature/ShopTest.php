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
