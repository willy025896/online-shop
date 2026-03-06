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

test('categories show page can be rendered', function () {
    $category = Category::factory()->create();

    $this->get(route('categories.show', $category->slug))
        ->assertStatus(200);
});