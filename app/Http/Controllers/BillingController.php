<?php

namespace App\Http\Controllers;

use App\Domains\Community\Actions\FulfillPaidCommunityDuaCheckoutAction;
use App\Domains\Billing\Actions\FulfillPremiumCheckoutAction;
use App\Domains\Billing\Actions\StartPremiumCheckoutAction;
use App\Domains\Billing\Services\StripeCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Arr;
use RuntimeException;

class BillingController extends Controller
{
    /**
     * @deprecated Use StartEmbeddedPurchaseCheckoutAction and embedded checkout instead.
     */
    public function checkout(StartPremiumCheckoutAction $action): RedirectResponse
    {
        Gate::authorize('start-billing-checkout');

        try {
            $checkout = $action(Auth::user());
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('dashboard.upgrade')
                ->withErrors(['billing' => $exception->getMessage()]);
        }

        return redirect()->away($checkout['session']['url']);
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

        $metadata = $this->metadata($session);

        if (($metadata['entitlement'] ?? '') === 'community_dua_paid') {
            app(FulfillPaidCommunityDuaCheckoutAction::class)($session);

            return redirect()
                ->route('community-dua.success', ['session_id' => $sessionId])
                ->with('status', 'Your paid community dua has been submitted.');
        }

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
            $metadata = $this->metadata($event->data->object);

            if (($metadata['entitlement'] ?? '') === 'community_dua_paid') {
                app(FulfillPaidCommunityDuaCheckoutAction::class)($event->data->object, $event->id);
            } else {
                $fulfill($event->data->object, $event->id);
            }
        }

        return response('', 204);
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
