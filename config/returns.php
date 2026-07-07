<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Return application window
    |--------------------------------------------------------------------------
    |
    | Number of days after an order's completed_at during which a buyer may
    | request a return/refund. Overridable via the ORDER_RETURN_WINDOW_DAYS
    | env var.
    |
    */
    'window_days' => (int) env('ORDER_RETURN_WINDOW_DAYS', 7),
];
