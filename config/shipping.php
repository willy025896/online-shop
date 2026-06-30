<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flat shipping fee
    |--------------------------------------------------------------------------
    |
    | The fixed shipping fee charged per shop (per order). Orders are split
    | per shop at checkout, so this fee is evaluated independently for each
    | shop's subtotal.
    |
    */
    'flat_fee' => (float) env('SHIPPING_FLAT_FEE', 100),

    /*
    |--------------------------------------------------------------------------
    | Free shipping threshold
    |--------------------------------------------------------------------------
    |
    | When a shop's subtotal reaches this amount, shipping for that shop is
    | free. Set to null (or remove the env value) to disable free shipping.
    |
    */
    'free_threshold' => env('SHIPPING_FREE_THRESHOLD', 1000) !== null
        ? (float) env('SHIPPING_FREE_THRESHOLD', 1000)
        : null,
];
