<?php

namespace App\Domains\Billing\Actions;

use App\Actions\Action;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\StripePayment;
use App\Models\User;
use Illuminate\Support\Arr;

class FulfillPremiumCheckoutAction extends Action
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {
    }

    public function handle(mixed ...$args): mixed
    {
        $session = $args[0];
        $eventId = $args[1] ?? null;

        $sessionId = (string) data_get($session, 'id');
        $paymentStatus = (string) data_get($session, 'payment_status', data_get($session, 'status'));

        if ($paymentStatus !== 'paid' && data_get($session, 'status') !== 'complete') {
            return null;
        }

        $metadata = $this->metadata($session);
        $userId = (int) (data_get($session, 'client_reference_id') ?: ($metadata['user_id'] ?? 0));
        $user = User::query()->findOrFail($userId);

        $payment = StripePayment::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $sessionId],
            [
                'user_id' => $user->id,
                'stripe_payment_intent_id' => data_get($session, 'payment_intent'),
                'stripe_event_id' => $eventId,
                'amount_total' => data_get($session, 'amount_total'),
                'currency' => data_get($session, 'currency'),
                'status' => StripePayment::STATUS_PAID,
                'metadata' => $metadata,
                'paid_at' => now(),
            ],
        );

        $this->entitlements->grantPremium($user, 'stripe_checkout', $sessionId, [
            'payment_id' => $payment->id,
            'stripe_event_id' => $eventId,
        ]);

        return $payment;
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(mixed $session): array
    {
        $metadata = data_get($session, 'metadata', []);

        if ($metadata instanceof \Stripe\StripeObject) {
            return $metadata->toArray();
        }

        return Arr::wrap($metadata);
    }
}
