<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
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

test('product show page exposes product and breadcrumb json-ld for a simple product', function () {
    $category = Category::factory()->create(['name' => '3C 用品']);
    $shop = Shop::factory()->create(['name' => '測試賣場']);
    $product = Product::factory()->create([
        'shop_id' => $shop->id,
        'category_id' => $category->id,
        'name' => '測試商品',
        'description' => '這是一個測試商品的描述',
        'price' => 199,
        'stock' => 5,
        'reviews_count' => 0,
    ]);
    ProductImage::create(['product_id' => $product->id, 'path' => 'products/test.jpg', 'sort_order' => 0]);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.@type', 'Product')
            ->where('seo.jsonLd.0.name', '測試商品')
            ->where('seo.jsonLd.0.category', '3C 用品')
            ->where('seo.jsonLd.0.brand.name', '測試賣場')
            ->where('seo.jsonLd.0.image.0', asset('storage/products/test.jpg'))
            ->where('seo.jsonLd.0.offers.@type', 'Offer')
            ->where('seo.jsonLd.0.offers.price', 199)
            ->where('seo.jsonLd.0.offers.priceCurrency', 'TWD')
            ->where('seo.jsonLd.0.offers.availability', 'https://schema.org/InStock')
            ->missing('seo.jsonLd.0.aggregateRating')
            ->where('seo.jsonLd.1.@type', 'BreadcrumbList')
            ->where('seo.jsonLd.1.itemListElement.1.name', '3C 用品')
            ->where('seo.jsonLd.1.itemListElement.2.name', '測試商品')
        );
});

test('product show page json-ld uses aggregate offer and includes rating when variants and reviews exist', function () {
    $product = Product::factory()->create(['price' => 100, 'stock' => 999, 'reviews_count' => 4, 'rating_sum' => 18]);

    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size']);
    $valueM = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'M']);
    $valueL = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'L']);

    $cheapVariant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 120, 'stock' => 0]);
    $cheapVariant->optionValues()->sync([$valueM->id]);

    $expensiveVariant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => 180, 'stock' => 3]);
    $expensiveVariant->optionValues()->sync([$valueL->id]);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.offers.@type', 'AggregateOffer')
            ->where('seo.jsonLd.0.offers.lowPrice', 120)
            ->where('seo.jsonLd.0.offers.highPrice', 180)
            ->where('seo.jsonLd.0.offers.offerCount', 2)
            ->where('seo.jsonLd.0.offers.availability', 'https://schema.org/InStock')
            ->where('seo.jsonLd.0.aggregateRating.ratingValue', 4.5)
            ->where('seo.jsonLd.0.aggregateRating.reviewCount', 4)
        );
});

test('shop show page exposes organization and breadcrumb json-ld', function () {
    $shop = Shop::factory()->create(['name' => '測試賣場', 'description' => '賣場描述', 'reviews_count' => 2, 'rating_sum' => 9]);

    $this->get(route('shops.show', $shop->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.@type', 'Organization')
            ->where('seo.jsonLd.0.name', '測試賣場')
            ->where('seo.jsonLd.0.aggregateRating.ratingValue', 4.5)
            ->where('seo.jsonLd.1.@type', 'BreadcrumbList')
            ->where('seo.jsonLd.1.itemListElement.1.name', __('navigation.shops'))
            ->where('seo.jsonLd.1.itemListElement.2.name', '測試賣場')
        );
});

test('category show page exposes breadcrumb json-ld including parent category', function () {
    $parent = Category::factory()->create(['name' => '家電']);
    $child = Category::factory()->create(['name' => '冷氣', 'parent_id' => $parent->id]);

    $this->get(route('categories.show', $child->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.@type', 'BreadcrumbList')
            ->where('seo.jsonLd.0.itemListElement.1.name', '家電')
            ->where('seo.jsonLd.0.itemListElement.2.name', '冷氣')
        );
});

test('product show page html response contains an application/ld+json script tag', function () {
    $product = Product::factory()->create();

    $this->get(route('products.show', $product->slug))
        ->assertSee('application/ld+json', false);
});

test('category show page breadcrumb json-ld walks multiple ancestor levels', function () {
    $grandparent = Category::factory()->create(['name' => '家電']);
    $parent = Category::factory()->create(['name' => '冷氣機', 'parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['name' => '窗型冷氣', 'parent_id' => $parent->id]);

    $this->get(route('categories.show', $child->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.itemListElement.1.name', '家電')
            ->where('seo.jsonLd.0.itemListElement.2.name', '冷氣機')
            ->where('seo.jsonLd.0.itemListElement.3.name', '窗型冷氣')
        );
});

test('category show page breadcrumb json-ld skips a deactivated ancestor', function () {
    $grandparent = Category::factory()->create(['name' => '家電', 'is_active' => false]);
    $parent = Category::factory()->create(['name' => '冷氣機', 'parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['name' => '窗型冷氣', 'parent_id' => $parent->id]);

    $this->get(route('categories.show', $child->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.0.itemListElement.1.name', '冷氣機')
            ->where('seo.jsonLd.0.itemListElement.2.name', '窗型冷氣')
        );
});

test('product show page breadcrumb json-ld omits the category segment when the product category is deactivated', function () {
    $category = Category::factory()->create(['name' => '停用分類', 'is_active' => false]);
    $product = Product::factory()->create(['category_id' => $category->id, 'name' => '測試商品']);

    $this->get(route('products.show', $product->slug))
        ->assertInertia(fn ($page) => $page
            ->where('seo.jsonLd.1.itemListElement.1.name', '測試商品')
        );
});
