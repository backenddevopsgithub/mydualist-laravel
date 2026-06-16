<?php

namespace App\Http\Controllers;

use App\Domains\Billing\Actions\StartEmbeddedPurchaseCheckoutAction;
use App\Domains\Billing\Services\PurchaseService;
use App\Domains\Billing\Support\PurchaseCheckoutRedirectResolver;
use App\Http\Requests\Billing\StartPurchaseCheckoutRequest;
use App\Models\BillingPurchase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use RuntimeException;

class PurchaseCheckoutController extends Controller
{
    public function store(
        StartPurchaseCheckoutRequest $request,
        StartEmbeddedPurchaseCheckoutAction $action,
    ): RedirectResponse {
        Gate::authorize('start-billing-checkout');

        try {
            $result = $action($request->user(), $request->checkoutPayload());
        } catch (AuthenticationException $exception) {
            return redirect()
                ->route('dashboard.upgrade')
                ->withErrors(['billing' => $exception->getMessage()]);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('dashboard.upgrade')
                ->withErrors(['billing' => $exception->getMessage()]);
        }

        return redirect()->route('billing.purchases.checkout', $result['purchase']);
    }

    public function show(
        Request $request,
        BillingPurchase $purchase,
        PurchaseService $purchases,
        PurchaseCheckoutRedirectResolver $redirects,
    ): View {
        try {
            $purchase = $purchases->findAccessible($purchase, $request->user());
        } catch (AuthenticationException) {
            abort(401, 'Authentication is required for this purchase.');
        } catch (AuthorizationException) {
            abort(403, 'You do not have access to this purchase.');
        }

        $stripeKey = config('services.stripe.key');

        if (! $stripeKey) {
            abort(503, 'Payment processing is not configured.');
        }

        return view('billing.purchase-checkout', [
            'purchaseId' => $purchase->id,
            'stripeKey' => $stripeKey,
            'returnUrl' => route('billing.purchases.checkout', $purchase),
            'successUrl' => $redirects->successUrl($purchase),
            'failureUrl' => $redirects->failureUrl($purchase),
            'continueLabel' => $redirects->continueLabel($purchase),
        ]);
    }
}
