<?php

use App\Models\Product;
use App\Models\SearchQuery;
use App\Models\Shop;

beforeEach(function () {
    $this->shop = Shop::factory()->create();
});

test('search query is recorded when products index is searched', function () {
    Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Red Widget']);

    $this->get(route('products.index', ['search' => 'Red Widget']))
        ->assertStatus(200);

    $this->assertDatabaseHas('search_queries', [
        'query' => 'red widget',
        'count' => 1,
    ]);
});

test('search suggestions returns hot queries and product suggestions', function () {
    Product::factory()->create(['shop_id' => $this->shop->id, 'name' => 'Blue Lantern']);
    SearchQuery::create(['query' => 'blue lan', 'count' => 5, 'last_searched_at' => now()]);

    $response = $this->getJson(route('search.suggestions', ['q' => 'Blue']));

    $response->assertStatus(200)
        ->assertJsonStructure([
            'products' => [
                '*' => ['type', 'id', 'name', 'slug', 'shop_name'],
            ],
            'hot_queries',
        ]);

    $this->assertSame('blue lan', $response->json('hot_queries.0'));
});

test('suggestions endpoint returns hot queries even when query is empty', function () {
    SearchQuery::create(['query' => 'popular item', 'count' => 3, 'last_searched_at' => now()]);

    $response = $this->getJson(route('search.suggestions', ['q' => '']));

    $response->assertStatus(200)
        ->assertJson(['products' => []])
        ->assertJsonCount(1, 'hot_queries');
});
