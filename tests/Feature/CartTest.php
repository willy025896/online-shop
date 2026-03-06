<?php

use App\Models\Product;

test('cart page can be rendered', function () {
    $this->get(route('cart.index'))->assertStatus(200);
});

test('product can be added to cart', function () {
    $product = Product::factory()->create(['stock' => 10]);

    $this->post(route('cart.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ])->assertRedirect();
});