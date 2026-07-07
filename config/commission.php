<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform commission rate
    |--------------------------------------------------------------------------
    |
    | Flat, platform-wide percentage (0.05 = 5%) taken from a shop's net goods
    | revenue (subtotal - discount - refunded_amount) at payout time. Never
    | applied to the shipping fee, which is passed through to the seller in
    | full. Overridable via the PLATFORM_COMMISSION_RATE env var.
    |
    */
    'rate' => (float) env('PLATFORM_COMMISSION_RATE', 0.05),
];
