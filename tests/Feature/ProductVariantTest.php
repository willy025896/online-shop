<?php

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\Shop;
use App\Models\User;

function makeProductWithVariant(int $stock = 10, float $price = 150.0): array
{
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id, 'price' => 100, 'stock' => 999]);

    $option = ProductOption::factory()->create(['product_id' => $product->id, 'name' => 'Size']);
    $valueM = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'M']);
    $valueL = ProductOptionValue::factory()->create(['product_option_id' => $option->id, 'value' => 'L']);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => $price,
        'stock' => $stock,
    ]);
    $variant->optionValues()->sync([$valueM->id]);

    $otherVariant = ProductVariant::factory()->create(['product_id' => $product->id]);
    $otherVariant->optionValues()->sync([$valueL->id]);

    return compact('seller', 'shop', 'product', 'option', 'valueM', 'valueL', 'variant', 'otherVariant');
}

test('seller can sync options and variants for a product', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($seller)
        ->patch(route('seller.products.variants.update', $product), [
            'options' => [
                [
                    'name' => 'Size',
                    'values' => [
                        ['key' => 'new-1', 'value' => 'M'],
                        ['key' => 'new-2', 'value' => 'L'],
                    ],
                ],
            ],
            'variants' => [
                ['sku' => 'SKU-M', 'price' => 120, 'stock' => 5, 'option_value_keys' => ['new-1']],
                ['sku' => 'SKU-L', 'price' => 130, 'stock' => 3, 'option_value_keys' => ['new-2']],
            ],
        ])
        ->assertRedirect();

    $product->refresh();
    expect($product->options()->count())->toBe(1);
    expect($product->variants()->count())->toBe(2);

    $variantM = $product->variants()->where('sku', 'SKU-M')->first();
    expect((float) $variantM->price)->toBe(120.0);
    expect($variantM->optionValues()->count())->toBe(1);
});

test('duplicate option combination is rejected', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $this->actingAs($seller)
        ->patch(route('seller.products.variants.update', $product), [
            'options' => [
                [
                    'name' => 'Size',
                    'values' => [
                        ['key' => 'new-1', 'value' => 'M'],
                    ],
                ],
            ],
            'variants' => [
                ['sku' => 'SKU-A', 'price' => 100, 'stock' => 5, 'option_value_keys' => ['new-1']],
                ['sku' => 'SKU-B', 'price' => 100, 'stock' => 5, 'option_value_keys' => ['new-1']],
            ],
        ])
        ->assertSessionHasErrors('variants');

    expect($product->variants()->count())->toBe(0);
});

test('another seller cannot edit variants of someone elses product', function () {
    $seller = User::factory()->seller()->create();
    $shop = Shop::factory()->create(['user_id' => $seller->id]);
    $product = Product::factory()->create(['shop_id' => $shop->id]);

    $otherSeller = User::factory()->seller()->create();
    Shop::factory()->create(['user_id' => $otherSeller->id]);

    $this->actingAs($otherSeller)
        ->patch(route('seller.products.variants.update', $product), [
            'options' => [],
            'variants' => [],
        ])
        ->assertForbidden();
});

test('product show page loads options and variants when product has variants', function () {
    ['product' => $product] = makeProductWithVariant();

    $this->get(route('products.show', $product->slug))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Products/Show')
            ->has('product.options', 1)
            ->has('product.variants', 2)
        );
});

test('buyer can add a specific variant to the cart and unit price follows the variant', function () {
    ['product' => $product, 'variant' => $variant] = makeProductWithVariant(stock: 10, price: 150.0);
    $buyer = User::factory()->create();

    $this->actingAs($buyer)
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])
        ->assertRedirect();

    $item = CartItem::where('product_variant_id', $variant->id)->first();
    expect($item)->not->toBeNull();
    expect((float) $item->unit_price)->toBe(150.0);
    expect($item->quantity)->toBe(2);
});

test('adding a variant product without variant_id is rejected', function () {
    ['product' => $product] = makeProductWithVariant();
    $buyer = User::factory()->create();

    $this->actingAs($buyer)
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('variant_id');
});

test('checkout locks variant stock, snapshots variant_label, and restores variant stock on cancellation', function () {
    ['product' => $product, 'variant' => $variant] = makeProductWithVariant(stock: 5, price: 150.0);
    $buyer = User::factory()->create();

    $cart = Cart::create(['user_id' => $buyer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => 150.0,
    ]);

    $this->actingAs($buyer)
        ->post(route('checkout.store'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Test St',
            'payment_method' => 'simulated',
        ])
        ->assertRedirect(route('orders.index'));

    expect($variant->fresh()->stock)->toBe(3);
    expect($product->fresh()->stock)->toBe(999); // product-level stock untouched

    $order = Order::where('user_id', $buyer->id)->firstOrFail();
    $orderItem = $order->items()->first();

    expect($orderItem->product_variant_id)->toBe($variant->id);
    expect($orderItem->variant_label)->toBe('Size: M');

    app(App\Services\OrderService::class)->directCancelByBuyer($order, 'Changed my mind');

    expect($variant->fresh()->stock)->toBe(5);
});

test('insufficient variant stock blocks checkout even when product stock is high', function () {
    ['product' => $product, 'variant' => $variant] = makeProductWithVariant(stock: 1, price: 150.0);
    $buyer = User::factory()->create();

    $cart = Cart::create(['user_id' => $buyer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 5,
        'unit_price' => 150.0,
    ]);

    $this->actingAs($buyer)
        ->post(route('checkout.store'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Test St',
            'payment_method' => 'simulated',
        ])
        ->assertSessionHasErrors('checkout');

    expect(Order::where('user_id', $buyer->id)->count())->toBe(0);
    expect($variant->fresh()->stock)->toBe(1);
});

test('checkout still resolves a soft-deleted variant instead of silently falling back to product stock', function () {
    ['product' => $product, 'variant' => $variant] = makeProductWithVariant(stock: 5, price: 150.0);
    $buyer = User::factory()->create();

    $cart = Cart::create(['user_id' => $buyer->id]);
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => 150.0,
    ]);

    // Seller removes this variant (soft delete) after the buyer already added it to the cart.
    $variant->delete();

    $this->actingAs($buyer)
        ->post(route('checkout.store'), [
            'shipping_name' => 'Test User',
            'shipping_phone' => '0912345678',
            'shipping_address' => '123 Test St',
            'payment_method' => 'simulated',
        ])
        ->assertRedirect(route('orders.index'));

    // The variant's own stock is decremented, not the parent product's.
    expect($variant->fresh()->stock)->toBe(3);
    expect($product->fresh()->stock)->toBe(999);

    $order = Order::where('user_id', $buyer->id)->firstOrFail();
    $orderItem = $order->items()->first();
    expect($orderItem->product_variant_id)->toBe($variant->id);
    expect($orderItem->variant_label)->toBe('Size: M');
});

test('adding a soft-deleted variant to the cart is rejected gracefully instead of a raw 404', function () {
    ['product' => $product, 'variant' => $variant] = makeProductWithVariant();
    $variant->delete();
    $buyer = User::factory()->create();

    $this->actingAs($buyer)
        ->post(route('cart.store'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])
        ->assertSessionHasErrors('variant_id');

    expect(CartItem::where('product_variant_id', $variant->id)->exists())->toBeFalse();
});
