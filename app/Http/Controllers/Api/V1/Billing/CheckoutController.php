<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Domains\Billing\Actions\FulfillPremiumCheckoutAction;
use App\Domains\Billing\Actions\StartPremiumCheckoutAction;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Domains\Billing\Services\UserEntitlementService;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Billing\StartCheckoutRequest;
use App\Http\Resources\Api\V1\Billing\BillingCheckoutResource;
use App\Http\Resources\Api\V1\Billing\CheckoutStatusResource;
use App\Models\StripePayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * @deprecated Legacy API Stripe Checkout Session endpoints. Use PurchaseController
 *             and embedded checkout until rollout monitoring is complete.
 */
class CheckoutController extends ApiController
{
    public function store(StartCheckoutRequest $request, StartPremiumCheckoutAction $action): JsonResponse
    {
        Gate::authorize('start-billing-checkout');

        try {
            $checkout = $action($request->user(), $request->checkoutOptions());
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), 503, 'stripe_unavailable');
        }

        return $this->success(
            (new BillingCheckoutResource($checkout))->resolve(),
            'Checkout session created.',
            201,
        );
    }

    public function show(
        string $sessionId,
        StripeCheckoutService $stripe,
        FulfillPremiumCheckoutAction $fulfill,
        UserEntitlementService $entitlements,
    ): JsonResponse {
        Gate::authorize('start-billing-checkout');

        $user = request()->user();

        $payment = StripePayment::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $session = $stripe->retrieveCheckoutSession($sessionId);

        abort_unless((string) data_get($session, 'client_reference_id') === (string) $user->id, 403);

        $fulfilled = false;

        if (
            (string) data_get($session, 'payment_status') === 'paid'
            || (string) data_get($session, 'status') === 'complete'
        ) {
            $fulfill($session);
            $fulfilled = true;
            $payment = $payment->fresh();
        }

        return $this->success(
            (new CheckoutStatusResource([
                'session_id' => $sessionId,
                'payment_status' => data_get($session, 'payment_status'),
                'status' => $payment->status,
                'has_premium' => $entitlements->hasPremium($user->fresh()),
                'fulfilled' => $fulfilled || $payment->status === StripePayment::STATUS_PAID,
            ]))->resolve(),
            'Checkout status retrieved.',
        );
    }
}
