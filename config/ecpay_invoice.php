<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ECPay (綠界) electronic invoice (B2C 電子發票) credentials
    |--------------------------------------------------------------------------
    |
    | Separate credential set from config/ecpay.php (payment gateway) — ECPay's
    | electronic invoice service uses a different HashKey/HashIV even though the
    | published stage MerchantID happens to be the same. See ADR-019.
    */
    'merchant_id' => env('ECPAY_INVOICE_MERCHANT_ID', '2000132'),
    'hash_key' => env('ECPAY_INVOICE_HASH_KEY', 'ejCk326UnaZWKisg'),
    'hash_iv' => env('ECPAY_INVOICE_HASH_IV', 'q9jcZX8Ib9LM8wYk'),

    'mode' => env('ECPAY_INVOICE_MODE', 'stage'),

    'base_urls' => [
        'stage' => [
            'issue' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Issue',
            'invalid' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Invalid',
            'allowance' => 'https://einvoice-stage.ecpay.com.tw/B2CInvoice/Allowance',
        ],
        'production' => [
            'issue' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Issue',
            'invalid' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Invalid',
            'allowance' => 'https://einvoice.ecpay.com.tw/B2CInvoice/Allowance',
        ],
    ],
];
