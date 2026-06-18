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

    'creator_mode' => [
        'enabled' => env('CREATOR_MODE_ENABLED', false),
    ],

    'moderation' => [
        'auto_hide_threshold' => env('MYDUALIST_MODERATION_AUTO_HIDE_THRESHOLD') !== null
            ? (int) env('MYDUALIST_MODERATION_AUTO_HIDE_THRESHOLD')
            : null,
    ],

    'admin_exports' => [
        'pending_timeout_minutes' => (int) env('ADMIN_EXPORT_PENDING_TIMEOUT_MINUTES', 15),
        'processing_timeout_minutes' => (int) env('ADMIN_EXPORT_PROCESSING_TIMEOUT_MINUTES', 35),
        'retention_days' => (int) env('ADMIN_EXPORT_RETENTION_DAYS', 7),
        'rate_limit_per_hour' => (int) env('ADMIN_EXPORT_RATE_LIMIT_PER_HOUR', 10),
        'download_url_ttl_days' => (int) env('ADMIN_EXPORT_DOWNLOAD_URL_TTL_DAYS', 7),
        'max_bulk_selection_rows' => (int) env('ADMIN_EXPORT_MAX_BULK_SELECTION_ROWS', 500),
    ],

    'user_exports' => [
        'rate_limit_per_hour' => (int) env('USER_EXPORT_RATE_LIMIT_PER_HOUR', 5),
    ],

    'analytics' => [
        // Coalesce analytics cache version bumps during write bursts (e.g. Arafah submissions).
        'cache_invalidation_debounce_seconds' => (int) env('MYDUALIST_ANALYTICS_CACHE_INVALIDATION_DEBOUNCE_SECONDS', 60),
    ],

    'admin_dashboard' => [
        // Filament dashboard widget aggregates (platform stats, charts, email health).
        'cache_ttl_seconds' => (int) env('MYDUALIST_ADMIN_DASHBOARD_CACHE_TTL_SECONDS', 600),
    ],

    'super_admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => mb_strtolower(trim($email)),
        explode(',', (string) env('MYDUALIST_SUPER_ADMIN_EMAILS', '')),
    ))),

    'notifications' => [
        'daily_digest_at' => env('MYDUALIST_DAILY_DIGEST_AT', '23:59'),
        'daily_digest_list_chunk' => 50,
        'reminder_list_chunk' => 50,
        'no_activity_hours' => 24,
        'closing_soon_hours_before_end' => 3,
        'list_image_hours_after_start' => 1,
    ],

    'legacy' => [
        'import' => [
            'batch_size' => (int) env('LEGACY_IMPORT_BATCH_SIZE', 100),
            'users_report_path' => storage_path('app/legacy-import-users-report.json'),
            'suggestions_report_path' => storage_path('app/legacy-import-suggestions-report.json'),
            'lists_report_path' => storage_path('app/legacy-import-lists-report.json'),
            'purchases_report_path' => storage_path('app/legacy-import-purchases-report.json'),
            'submissions_report_path' => storage_path('app/legacy-import-submissions-report.json'),
            'community_duas_report_path' => storage_path('app/legacy-import-community-duas-report.json'),
            'validate_report_path' => storage_path('app/legacy-import-validate-report.json'),
        ],
    ],

    'blog' => [
        'bismillah_image_url' => env(
            'MYDUALIST_BISMILLAH_IMAGE_URL',
            'https://thepilgrim.co/wp-content/uploads/2024/03/arabic-calligraphy-bismillah-first-verse-600nw-707955160-e1710954419173.webp',
        ),
        'import' => [
            'default_category_slug' => env('BLOG_IMPORT_DEFAULT_CATEGORY', 'essentials'),
            'report_path' => storage_path('app/blog-import-report.json'),
            'wordpress_origins' => array_filter([
                env('BLOG_IMPORT_WP_ORIGIN'),
                'https://thepilgrim.co',
                'https://www.thepilgrim.co',
                'https://mydualist.com',
                'https://www.mydualist.com',
            ]),
        ],
    ],

];
