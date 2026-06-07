<?php

namespace App\Http\Controllers;

use App\Domains\Billing\Actions\FulfillPremiumCheckoutAction;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Models\StripePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class BillingController extends Controller
{
    public function checkout(StripeCheckoutService $stripe): RedirectResponse
    {
        Gate::authorize('start-billing-checkout');

        try {
            $session = $stripe->createPremiumCheckout(Auth::user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('dashboard.upgrade')
                ->withErrors(['billing' => $exception->getMessage()]);
        }

        StripePayment::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $session['id']],
            [
                'user_id' => Auth::id(),
                'amount_total' => $session['amount_total'],
                'currency' => $session['currency'],
                'status' => StripePayment::STATUS_PENDING,
                'metadata' => ['entitlement' => 'premium'],
            ],
        );

        return redirect()->away($session['url']);
    }

    public function success(
        Request $request,
        StripeCheckoutService $stripe,
        FulfillPremiumCheckoutAction $fulfill,
    ): RedirectResponse {
        $sessionId = (string) $request->query('session_id');

        if ($sessionId === '') {
            return redirect()->route('dashboard.upgrade')->withErrors(['billing' => 'Missing Stripe session.']);
        }

        $session = $stripe->retrieveCheckoutSession($sessionId);

        abort_unless((string) data_get($session, 'client_reference_id') === (string) Auth::id(), 403);

        $fulfill($session);

        return redirect()
            ->route('dashboard.upgrade')
            ->with('status', 'Premium unlocked successfully.');
    }

    public function webhook(
        Request $request,
        StripeCheckoutService $stripe,
        FulfillPremiumCheckoutAction $fulfill,
    ): Response {
        $event = $stripe->constructWebhookEvent(
            $request->getContent(),
            (string) $request->header('Stripe-Signature'),
        );

        if ($event->type === 'checkout.session.completed') {
            $fulfill($event->data->object, $event->id);
        }

        return response('', 204);
    }
}
