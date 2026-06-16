<?php

return [

    'currency' => env('MYDUALIST_BILLING_CURRENCY', 'gbp'),

    'free_visible_submissions_per_list' => (int) env('MYDUALIST_FREE_VISIBLE_SUBMISSIONS', 25),

    'unlimited_list_submission_cap' => (int) env('MYDUALIST_UNLIMITED_LIST_SUBMISSION_CAP', 1500),

    'default_list_capacity' => (int) env('MYDUALIST_DEFAULT_LIST_CAPACITY', 2),

    'request_pack_size' => (int) env('MYDUALIST_REQUEST_PACK_SIZE', 25),

    'request_pack_unlock_batch' => (int) env('MYDUALIST_REQUEST_PACK_UNLOCK_BATCH', 25),

    'products' => [
        'REQUEST_PACK_25' => [
            'external_id' => 728,
            'name' => '25 Dua Requests',
            'scope' => 'list',
            'stackable' => true,
            'amount_minor' => 200,
            'requires_authentication' => true,
        ],
        'UNLIMITED_ONE_LIST' => [
            'external_id' => 730,
            'name' => 'Unlimited One List',
            'scope' => 'list',
            'stackable' => false,
            'amount_minor' => 799,
            'requires_authentication' => true,
        ],
        'ADDITIONAL_LIST' => [
            'external_id' => 914,
            'name' => 'One Additional List',
            'scope' => 'user',
            'stackable' => true,
            'amount_minor' => 799,
            'requires_authentication' => true,
        ],
        'UNLIMITED_FOREVER' => [
            'external_id' => 731,
            'name' => 'Unlimited Forever',
            'scope' => 'user',
            'stackable' => false,
            'amount_minor' => 1199,
            'requires_authentication' => true,
        ],
        'COMMUNITY_DUA_PAID' => [
            'external_id' => 3211,
            'name' => 'Paid Community Dua',
            'scope' => 'community_dua',
            'stackable' => false,
            'amount_minor' => 1000,
            'requires_authentication' => false,
        ],
    ],

    'monitoring' => [
        'unfulfilled_purchase_alert_threshold' => (int) env('MYDUALIST_BILLING_UNFULFILLED_ALERT_THRESHOLD', 5),
        'alert_slack_webhook' => env('MYDUALIST_BILLING_ALERT_SLACK_WEBHOOK'),
    ],

    'production' => [
        'webhook_events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'charge.refunded',
            'charge.dispute.created',
        ],
        'legacy_webhook_endpoint' => '/stripe/webhook',
        'primary_webhook_endpoint' => '/api/v1/billing/webhooks/stripe',
    ],

];
