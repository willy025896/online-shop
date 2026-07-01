<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Low stock threshold
    |--------------------------------------------------------------------------
    |
    | A product is considered "low stock" when its remaining stock is at or
    | below this value (0 = out of stock is the most urgent case). Used by
    | the seller dashboard low-stock alert and the products-list low_stock
    | filter. Overridable via the INVENTORY_LOW_STOCK_THRESHOLD env var.
    |
    */
    'low_stock_threshold' => (int) env('INVENTORY_LOW_STOCK_THRESHOLD', 5),
];
