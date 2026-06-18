<?php

namespace App\Services;

class FeatureFlagService extends Service
{
    /**
     * @return list<array{key: string, label: string, enabled: bool, source: string}>
     */
    public function flags(): array
    {
        return [
            [
                'key' => 'creator_mode',
                'label' => 'Creator Mode',
                'enabled' => (bool) config('mydualist.creator_mode.enabled'),
                'source' => 'CREATOR_MODE_ENABLED',
            ],
            [
                'key' => 'mailchimp',
                'label' => 'Mailchimp Integration',
                'enabled' => (bool) config('services.mailchimp.enabled'),
                'source' => 'MAILCHIMP_ENABLED',
            ],
            [
                'key' => 'billing_checkout',
                'label' => 'Billing Checkout',
                'enabled' => config('billing.stripe.secret') !== null,
                'source' => 'STRIPE_SECRET',
            ],
        ];
    }
}
