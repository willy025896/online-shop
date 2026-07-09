<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ECPay (綠界) merchant credentials
    |--------------------------------------------------------------------------
    |
    | Default to ECPay's officially published stage (test) merchant credentials
    | so local development works with zero setup — no merchant account needed.
    | Override via env for a real production merchant account.
    */
    'merchant_id' => env('ECPAY_MERCHANT_ID', '2000132'),
    'hash_key' => env('ECPAY_HASH_KEY', '5294y06JbISpM5x9'),
    'hash_iv' => env('ECPAY_HASH_IV', 'v77hoKGq4kWxNNIS'),

    /*
    |--------------------------------------------------------------------------
    | Mode
    |--------------------------------------------------------------------------
    |
    | 'stage' (ECPay's sandbox) or 'production'. Selects which base URLs below
    | are used.
    */
    'mode' => env('ECPAY_MODE', 'stage'),

    'base_urls' => [
        'stage' => [
            'checkout' => 'https://payment-stage.ecpay.com.tw/Cashier/AioCheckOut/V5',
            'credit_card_action' => 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction',
        ],
        'production' => [
            'checkout' => 'https://payment.ecpay.com.tw/Cashier/AioCheckOut/V5',
            'credit_card_action' => 'https://payment.ecpay.com.tw/CreditDetail/DoAction',
        ],
    ],
];
