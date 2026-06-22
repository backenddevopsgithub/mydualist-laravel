<?php

namespace App\Domains\Billing\Services;

use App\Models\BillingPurchase;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class StripePaymentIntentService
{
    /**
     * @return array{id: string, client_secret: string|null}
     */
    public function createForPurchase(BillingPurchase $purchase): array
    {
        $intent = $this->client()->paymentIntents->create([
            'amount' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'automatic_payment_methods' => $this->automaticPaymentMethods(),
            'metadata' => $this->metadataForPurchase($purchase),
        ], [
            'idempotency_key' => 'purchase:'.$purchase->idempotency_key,
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }

    /**
     * @return array{id: string, client_secret: string|null}
     */
    public function retrieve(string $paymentIntentId): array
    {
        $intent = $this->client()->paymentIntents->retrieve($paymentIntentId, []);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }

    public function confirmInTestMode(string $paymentIntentId): PaymentIntent
    {
        $secret = (string) config('services.stripe.secret');

        if (! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('Test-mode Payment Intent confirmation requires a Stripe test secret key.');
        }

        try {
            return $this->client()->paymentIntents->confirm($paymentIntentId, [
                'payment_method' => 'pm_card_visa',
            ]);
        } catch (ApiErrorException $exception) {
            $intent = $this->retrieveIntent($paymentIntentId);

            if ($this->usesRedirectPaymentMethods($intent)) {
                throw new RuntimeException(
                    'This Payment Intent allows redirect-based payment methods and cannot be confirmed with pm_card_visa. Create a new purchase after allow_redirects=never is enabled, or rerun with --local-only.',
                    previous: $exception,
                );
            }

            throw $exception;
        }
    }

    public function retrieveIntent(string $paymentIntentId): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($paymentIntentId, []);
    }

    /**
     * @return array{id: string, amount: int, status: string}
     */
    public function refundPaymentIntent(string $paymentIntentId, ?int $amountMinor = null): array
    {
        $params = ['payment_intent' => $paymentIntentId];

        if ($amountMinor !== null) {
            $params['amount'] = $amountMinor;
        }

        $refund = $this->client()->refunds->create($params, [
            'idempotency_key' => 'refund:'.$paymentIntentId.':'.($amountMinor ?? 'full'),
        ]);

        return [
            'id' => $refund->id,
            'amount' => (int) $refund->amount,
            'status' => (string) $refund->status,
        ];
    }

    public function usesRedirectPaymentMethods(PaymentIntent $intent): bool
    {
        $allowRedirects = (string) data_get(
            $intent,
            'automatic_payment_methods.allow_redirects',
            'always',
        );

        return $allowRedirects !== 'never';
    }

    /**
     * @return array{enabled: bool, allow_redirects: string}
     */
    public function automaticPaymentMethods(): array
    {
        return [
            'enabled' => true,
            'allow_redirects' => 'never',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function metadataForPurchase(BillingPurchase $purchase): array
    {
        return [
            'billing_purchase_id' => (string) $purchase->id,
            'billing_product_code' => (string) optional($purchase->product)->code,
            'billing_user_id' => $purchase->user_id ? (string) $purchase->user_id : '',
            'billing_dua_list_id' => $purchase->dua_list_id ? (string) $purchase->dua_list_id : '',
            'billing_community_dua_id' => $purchase->community_dua_id ? (string) $purchase->community_dua_id : '',
            'billing_idempotency_key' => $purchase->idempotency_key,
        ];
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
