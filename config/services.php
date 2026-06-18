<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'otp_template_sid' => env('TWILIO_OTP_TEMPLATE_SID'),
        'completion_template_sid' => env('TWILIO_COMPLETION_TEMPLATE_SID'),
        'creator_completion_template_sid' => env('TWILIO_CREATOR_COMPLETION_TEMPLATE_SID'),
        'salawat_template_sid' => env('TWILIO_SALAWAT_TEMPLATE_SID'),
        'test_otp' => env('TWILIO_TEST_OTP'),
    ],

    'mailchimp' => [
        'enabled' => env('MAILCHIMP_ENABLED', false),
        'api_key' => env('MAILCHIMP_API_KEY'),
        'server_prefix' => env('MAILCHIMP_SERVER_PREFIX'),
        'audience_id' => env('MAILCHIMP_AUDIENCE_ID'),
    ],

];
