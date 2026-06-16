<?php

namespace App\Domains\Billing\Actions;

use App\Actions\Action;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Models\StripePayment;
use App\Models\User;

/**
 * @deprecated Use StartEmbeddedPurchaseCheckoutAction with product-specific billing purchases instead.
 */
class StartPremiumCheckoutAction extends Action
{
    public function __construct(
        private readonly StripeCheckoutService $stripeCheckoutService,
    ) {}

    /**
     * @param  array{success_url?: string|null, cancel_url?: string|null}  $options
     * @return array{session: array{id: string, url: string, amount_total: int|null, currency: string|null}, payment: StripePayment}
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $options = $args[1] ?? [];

        $session = $this->stripeCheckoutService->createPremiumCheckout(
            $user,
            $options['success_url'] ?? null,
            $options['cancel_url'] ?? null,
        );

        $payment = StripePayment::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $session['id']],
            [
                'user_id' => $user->id,
                'amount_total' => $session['amount_total'],
                'currency' => $session['currency'],
                'status' => StripePayment::STATUS_PENDING,
                'metadata' => ['entitlement' => 'premium'],
            ],
        );

        return [
            'session' => $session,
            'payment' => $payment,
        ];
    }
}
