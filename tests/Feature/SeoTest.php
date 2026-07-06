<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Shop;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

test('sitemap includes active products, approved shops and active categories', function () {
    $shop = Shop::factory()->create();
    $product = Product::factory()->create(['shop_id' => $shop->id]);
    $category = Category::factory()->create();

    $response = $this->get(route('sitemap'));

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee(route('products.show', $product->slug), false);
    $response->assertSee(route('shops.show', $shop->slug), false);
    $response->assertSee(route('categories.show', $category->slug), false);
});

test('sitemap excludes draft products and unapproved shops', function () {
    $draftProduct = Product::factory()->draft()->create();
    $pendingShop = Shop::factory()->pending()->create();

    $response = $this->get(route('sitemap'));

    $response->assertDontSee(route('products.show', $draftProduct->slug), false);
    $response->assertDontSee(route('shops.show', $pendingShop->slug), false);
});

test('sitemap excludes active products whose shop is suspended', function () {
    $suspendedShop = Shop::factory()->suspended()->create();
    $product = Product::factory()->create(['shop_id' => $suspendedShop->id]);

    $response = $this->get(route('sitemap'));

    $response->assertDontSee(route('products.show', $product->slug), false);
});

test('sitemap caches urls per request host', function () {
    $product = Product::factory()->create();

    $this->get('http://shop-one.test/sitemap.xml')
        ->assertSee('http://shop-one.test/products/'.$product->slug, false);

    $this->get('http://shop-two.test/sitemap.xml')
        ->assertSee('http://shop-two.test/products/'.$product->slug, false)
        ->assertDontSee('http://shop-one.test', false);
});

test('robots.txt points to the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertStatus(200);
    $response->assertSee(route('sitemap'), false);
});

test('product show page exposes seo prop for og meta', function () {
    $product = Product::factory()->create(['name' => '測試商品', 'description' => '這是一個測試商品的描述']);
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/test.jpg', 'sort_order' => 0]);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', '測試商品')
            ->where('seo.description', '這是一個測試商品的描述')
            ->where('seo.image', asset('storage/products/test.jpg'))
            ->where('seo.url', route('products.show', $product->slug))
        );
});

test('shop show page exposes seo prop for og meta', function () {
    $shop = Shop::factory()->create(['name' => '測試賣場', 'description' => '賣場描述']);

    $this->get(route('shops.show', $shop->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', '測試賣場')
            ->where('seo.description', '賣場描述')
            ->where('seo.url', route('shops.show', $shop->slug))
        );
});

test('draft product show page does not expose seo meta', function () {
    $product = Product::factory()->draft()->create(['name' => '未上架商品']);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('isAvailable', false)
            ->where('seo', null)
        );
});

test('category show page exposes seo prop for og meta', function () {
    $category = Category::factory()->create(['name' => '測試分類']);

    $this->get(route('categories.show', $category->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.title', '測試分類')
            ->where('seo.url', route('categories.show', $category->slug))
        );
});
