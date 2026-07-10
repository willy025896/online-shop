<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;

test('categories show page sorts by price ascending', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $cheap = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 100]);
    $expensive = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('categories.show', $category->slug).'?sort=price_asc')
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $cheap->id)
            ->where('products.data.1.id', $expensive->id)
        );
});

test('categories show page sorts by price descending', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $cheap = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 100]);
    $expensive = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('categories.show', $category->slug).'?sort=price_desc')
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $expensive->id)
            ->where('products.data.1.id', $cheap->id)
        );
});

test('categories show page sorts by highest rating', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $lowRated = Product::factory()->create([
        'category_id' => $category->id, 'shop_id' => $shop->id, 'rating_sum' => 6, 'reviews_count' => 2,
    ]);
    $highRated = Product::factory()->create([
        'category_id' => $category->id, 'shop_id' => $shop->id, 'rating_sum' => 10, 'reviews_count' => 2,
    ]);

    $this->get(route('categories.show', $category->slug).'?sort=rating_desc')
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $highRated->id)
            ->where('products.data.1.id', $lowRated->id)
        );
});

test('categories show page sorts by name ascending', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $zebra = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'name' => 'Zebra']);
    $apple = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'name' => 'Apple']);

    $this->get(route('categories.show', $category->slug).'?sort=name')
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $apple->id)
            ->where('products.data.1.id', $zebra->id)
        );
});

test('categories show page defaults to latest sort when no sort param is given', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    $older = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'created_at' => now()->subDay()]);
    $newer = Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'created_at' => now()]);

    $this->get(route('categories.show', $category->slug))
        ->assertInertia(fn ($page) => $page
            ->where('products.data.0.id', $newer->id)
            ->where('products.data.1.id', $older->id)
        );
});

test('categories show page filters by min rating', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    Product::factory()->create([
        'category_id' => $category->id, 'shop_id' => $shop->id, 'rating_sum' => 6, 'reviews_count' => 2,
    ]);
    $highRated = Product::factory()->create([
        'category_id' => $category->id, 'shop_id' => $shop->id, 'rating_sum' => 10, 'reviews_count' => 2,
    ]);

    $this->get(route('categories.show', $category->slug).'?min_rating=5')
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.id', $highRated->id)
        );
});

test('categories show page filters by min price', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('categories.show', $category->slug).'?min_price=200')
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('categories show page filters by max price', function () {
    $category = Category::factory()->create();
    $shop = Shop::factory()->create();
    Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 100]);
    Product::factory()->create(['category_id' => $category->id, 'shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('categories.show', $category->slug).'?max_price=200')
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('categories show page sorting includes products from child categories', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $shop = Shop::factory()->create();
    $cheap = Product::factory()->create(['category_id' => $parent->id, 'shop_id' => $shop->id, 'price' => 100]);
    $expensive = Product::factory()->create(['category_id' => $child->id, 'shop_id' => $shop->id, 'price' => 500]);

    $this->get(route('categories.show', $parent->slug).'?sort=price_desc')
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data.0.id', $expensive->id)
            ->where('products.data.1.id', $cheap->id)
        );
});
