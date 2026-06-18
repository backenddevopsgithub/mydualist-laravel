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
        'free_list_limit' => config('billing.default_list_capacity', 2),
        'free_visible_submissions_per_list' => config('billing.free_visible_submissions_per_list', 25),
        'premium_price' => env('MYDUALIST_PREMIUM_PRICE', '12.99'),
        'premium_currency' => config('billing.currency', 'gbp'),
        'community_dua_price' => env('MYDUALIST_COMMUNITY_DUA_PRICE', '10.00'),
        'checkout_success_url' => env('MYDUALIST_STRIPE_SUCCESS_URL'),
        'checkout_cancel_url' => env('MYDUALIST_STRIPE_CANCEL_URL'),
    ],

    'brand' => [
        'logo_url' => env('MYDUALIST_LOGO_URL', 'images/logo-mdl.svg'),
    ],

    'onboarding' => [
        'test_otp' => env('MYDUALIST_TEST_OTP', '0000'),
    ],

    'notifications' => [
        'daily_digest_at' => env('MYDUALIST_DAILY_DIGEST_AT', '23:59'),
        'daily_digest_list_chunk' => 50,
        'reminder_list_chunk' => 50,
        'no_activity_hours' => 24,
        'closing_soon_hours_before_end' => 3,
        'list_image_hours_after_start' => 1,
    ],

];
