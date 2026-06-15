<?php

namespace App\Domains\Billing\Services;

use App\Models\CommunityDua;
use App\Models\User;
use App\Services\Service;
use RuntimeException;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeCheckoutService extends Service
{
    /**
     * @return array{id: string, url: string, amount_total: int|null, currency: string|null}
     */
    public function createPremiumCheckout(
        User $user,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): array {
        $client = $this->client();
        $amount = (int) round(((float) config('mydualist.billing.premium_price', '11.99')) * 100);
        $currency = (string) config('mydualist.billing.premium_currency', 'usd');

        $successUrl ??= config('mydualist.billing.checkout_success_url')
            ?? route('billing.success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl ??= config('mydualist.billing.checkout_cancel_url')
            ?? route('dashboard.upgrade');

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'entitlement' => 'premium',
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => 'MyDualist Premium',
                        'description' => 'Unlimited lists and unlocked dua submissions.',
                    ],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'amount_total' => $session->amount_total,
            'currency' => $session->currency,
        ];
    }

    /**
     * @return array{id: string, url: string, amount_total: int|null, currency: string|null}
     */
    public function createCommunityDuaCheckout(
        CommunityDua $communityDua,
        ?User $user = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null,
    ): array {
        $client = $this->client();
        $amount = (int) round(((float) config('mydualist.billing.community_dua_price', '10.00')) * 100);
        $currency = (string) config('mydualist.billing.premium_currency', 'gbp');

        $successUrl ??= route('community-dua.success').'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl ??= route('community-dua.create');

        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $communityDua->email,
            'client_reference_id' => $user ? (string) $user->id : (string) $communityDua->id,
            'metadata' => [
                'entitlement' => 'community_dua_paid',
                'community_dua_id' => (string) $communityDua->id,
                'user_id' => $user ? (string) $user->id : '',
            ],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => 'Pay It Forward — Community Dua',
                        'description' => 'Get your community dua seen by more pilgrims.',
                    ],
                ],
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
            'amount_total' => $session->amount_total,
            'currency' => $session->currency,
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): mixed
    {
        return $this->client()->checkout->sessions->retrieve($sessionId, []);
    }

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        return Webhook::constructEvent($payload, $signature, $secret);
    }

    private function client(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return new StripeClient($secret);
    }
}
