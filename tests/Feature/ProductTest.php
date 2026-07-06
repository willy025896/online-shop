<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;

test('products index page can be rendered', function () {
    $this->get(route('products.index'))->assertStatus(200);
});

test('products index displays products', function () {
    $shop = Shop::factory()->create();
    Product::factory(3)->create(['shop_id' => $shop->id]);

    $this->get(route('products.index'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Products/Index')
            ->has('products.data', 3)
        );
});

test('product show page can be rendered', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product->slug))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Products/Show')
            ->has('product')
        );
});

test('draft product show page renders as unavailable instead of 404', function () {
    $product = Product::factory()->draft()->create();

    $this->get(route('products.show', $product->slug))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Products/Show')
            ->where('isAvailable', false)
        );
});

test('categories show page can be rendered', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category->slug))
        ->assertStatus(200);
});

test('products index filters by min price', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('products.index', ['min_price' => 200]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('products index filters by max price', function () {
    $shop = Shop::factory()->create();
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('products.index', ['max_price' => 200]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});
