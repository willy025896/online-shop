<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\UploadedFile;

function csvSeller(): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);

    return [$seller, $shop];
}

test('seller can export their products as csv', function () {
    [$seller, $shop] = csvSeller();
    Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Exported Widget', 'description' => 'Simple description', 'price' => 19.99]);
    Product::factory()->create(['shop_id' => Shop::factory()->create()->id]); // other shop, excluded

    $response = $this->actingAs($seller)->get(route('seller.products.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $response->streamedContent();
    expect($content)->toContain('name,description,category,price,compare_price,stock,status,is_featured');
    expect($content)->toContain('Exported Widget');
    expect(substr_count($content, "\n"))->toBe(2); // header + 1 product row (+ trailing newline)
});

test('importing a csv creates new products and updates existing ones by name', function () {
    [$seller, $shop] = csvSeller();
    $category = Category::factory()->create(['name' => 'Gadgets']);
    $existing = Product::factory()->create(['shop_id' => $shop->id, 'name' => 'Existing Product', 'price' => 10]);

    $csv = <<<'CSV'
    name,description,category,price,compare_price,stock,status,is_featured
    Existing Product,Updated description,Gadgets,25.00,,10,active,0
    Brand New Product,A new one,Gadgets,15.50,,5,draft,1
    CSV;

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $this->actingAs($seller)
        ->post(route('seller.products.import'), ['file' => $file])
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Seller/Products/Import')
            ->where('result.created', 1)
            ->where('result.updated', 1)
            ->where('result.failed', [])
        );

    expect((float) $existing->fresh()->price)->toBe(25.0);
    expect($existing->fresh()->category_id)->toBe($category->id);

    $newProduct = Product::where('shop_id', $shop->id)->where('name', 'Brand New Product')->first();
    expect($newProduct)->not->toBeNull();
    expect($newProduct->status)->toBe(Product::STATUS_DRAFT);
});

test('a bad row is reported as a failure without blocking the rest of the import', function () {
    [$seller, $shop] = csvSeller();

    $csv = <<<'CSV'
    name,description,category,price,compare_price,stock,status,is_featured
    Valid Product,Ok,,9.99,,3,active,0
    ,Missing name,,5.00,,1,active,0
    CSV;

    $file = UploadedFile::fake()->createWithContent('products.csv', $csv);

    $this->actingAs($seller)
        ->post(route('seller.products.import'), ['file' => $file])
        ->assertInertia(fn ($page) => $page
            ->where('result.created', 1)
            ->has('result.failed', 1)
            ->where('result.failed.0.row', 3)
        );

    expect(Product::where('shop_id', $shop->id)->where('name', 'Valid Product')->exists())->toBeTrue();
});

test('customer cannot import products', function () {
    $file = UploadedFile::fake()->createWithContent('products.csv', "name,price,stock,status\n");

    $this->actingAs(User::factory()->create())
        ->post(route('seller.products.import'), ['file' => $file])
        ->assertForbidden();
});
