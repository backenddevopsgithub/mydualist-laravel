<?php

return [

    'name' => env('MYDUALIST_NAME', 'MyDualist'),

    'api' => [
        'version' => 'v1',
        'prefix' => 'api/v1',
    ],

    'defaults' => [
        'pagination' => [
            'per_page' => 15,
            'max_per_page' => 100,
        ],
    ],

    'billing' => [
        'free_list_limit' => 2,
        'free_visible_submissions_per_list' => 25,
        'premium_price' => env('MYDUALIST_PREMIUM_PRICE', '12.99'),
        'premium_currency' => env('MYDUALIST_PREMIUM_CURRENCY', 'gbp'),
        'checkout_success_url' => env('MYDUALIST_STRIPE_SUCCESS_URL'),
        'checkout_cancel_url' => env('MYDUALIST_STRIPE_CANCEL_URL'),
    ],

    'brand' => [
        'logo_url' => env('MYDUALIST_LOGO_URL', 'https://mydualist.com/wp-content/uploads/2025/05/logo-mdl.svg'),
    ],

    'onboarding' => [
        'test_otp' => env('MYDUALIST_TEST_OTP', '0000'),
    ],

];
