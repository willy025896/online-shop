<?php

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