<?php

/*
|--------------------------------------------------------------------------
| Online payments: Payme (Merchant API) and Click (SHOP API)
|--------------------------------------------------------------------------
|
| Payme Merchant API  — https://developer.help.paycom.uz/metody-merchant-api/
|   protocol / auth   — https://developer.help.paycom.uz/protokol-merchant-api/skhema-vzaimodeystviya
|   errors            — https://developer.help.paycom.uz/metody-merchant-api/oshibki-errors
|   checkout (GET)    — https://developer.help.paycom.uz/initsializatsiya-platezhey/otpravka-cheka-po-metodu-get
|   sandbox           — https://developer.help.paycom.uz/pesochnitsa
| Click SHOP API      — https://docs.click.uz/shop-api/requests , https://docs.click.uz/shop-api/errors
|   payment button    — https://docs.click.uz/click-button
|
| A provider is offered to students only when its credentials are filled in.
| See docs/payments/PAYMENTS.md for the merchant registration steps.
*/

return [

    'currency' => 'UZS',

    // A pending order of the same product/provider/amount is reused for this long
    // instead of creating a new one every time the student presses "Pay".
    'order_reuse_minutes' => (int) env('PAYMENT_ORDER_REUSE_MINUTES', 60),

    // CancelTransaction after PerformTransaction (a refund made in the Payme
    // cabinet) takes back what was granted: course access, coins (only while
    // the balance covers them), subscription time, olympiad seat (only before
    // the exam started). false = such refunds are refused with -31007.
    'reverse_on_refund' => (bool) env('PAYMENT_REVERSE_ON_REFUND', true),

    'payme' => [
        // "ID кассы" from the Payme Business cabinet (merchant / web-cashbox id)
        'merchant_id' => env('PAYME_MERCHANT_ID'),
        // Basic auth login is always "Paycom"; the password is the cashbox key
        'login' => env('PAYME_LOGIN', 'Paycom'),
        'key' => env('PAYME_KEY'),
        'test_key' => env('PAYME_TEST_KEY'),
        // true: the TEST_KEY is accepted and students are sent to test.paycom.uz
        'test_mode' => (bool) env('PAYME_TEST_MODE', true),
        'checkout_url' => env('PAYME_CHECKOUT_URL', 'https://checkout.paycom.uz'),
        'test_checkout_url' => env('PAYME_TEST_CHECKOUT_URL', 'https://test.paycom.uz'),
        // the Account field configured in the cashbox ("ac.order_id")
        'account_field' => env('PAYME_ACCOUNT_FIELD', 'order_id'),
        // CreateTransaction: a transaction is cancelled by timeout after 12 h
        'timeout_ms' => 43_200_000,
        'lang' => env('PAYME_LANG', 'uz'),
        // ct — ms to wait on the success screen before returning the student
        'return_timeout_ms' => (int) env('PAYME_RETURN_TIMEOUT_MS', 15000),
        // optional; "{order}" is replaced with the order's public id
        'return_url' => env('PAYME_RETURN_URL'),
        // Payme Business sends requests only from these addresses (Схема взаимодействия)
        'ip_check' => (bool) env('PAYME_IP_CHECK', false),
        'allowed_ips' => [
            '185.234.113.1', '185.234.113.2', '185.234.113.3', '185.234.113.4', '185.234.113.5',
            '185.234.113.6', '185.234.113.7', '185.234.113.8', '185.234.113.9', '185.234.113.10',
            '185.234.113.11', '185.234.113.12', '185.234.113.13', '185.234.113.14', '185.234.113.15',
        ],
        // Fiscal receipt data returned in CheckPerformTransaction.detail (optional).
        // IKPU (МХИК) and package code come from tasnif.soliq.uz for "education services".
        'fiscal' => [
            'ikpu' => env('PAYME_IKPU_CODE'),
            'package_code' => env('PAYME_PACKAGE_CODE'),
            'vat_percent' => (int) env('PAYME_VAT_PERCENT', 0),
        ],
    ],

    'click' => [
        'service_id' => env('CLICK_SERVICE_ID'),
        'merchant_id' => env('CLICK_MERCHANT_ID'),
        'secret_key' => env('CLICK_SECRET_KEY'),
        'merchant_user_id' => env('CLICK_MERCHANT_USER_ID'),
        'pay_url' => env('CLICK_PAY_URL', 'https://my.click.uz/services/pay'),
        // optional: uzcard | humo
        'card_type' => env('CLICK_CARD_TYPE'),
        // optional; "{order}" is replaced with the order's public id
        'return_url' => env('CLICK_RETURN_URL'),
        // Click does not publish a fixed list; fill it from the Click manager if wanted
        'ip_check' => (bool) env('CLICK_IP_CHECK', false),
        'allowed_ips' => array_values(array_filter(array_map('trim', explode(',', (string) env('CLICK_ALLOWED_IPS', ''))))),
    ],
];
