<?php

namespace App\Http\Controllers;

use App\Domains\Community\Actions\CreateFreeCommunityDuaAction;
use App\Domains\Community\Actions\FulfillPaidCommunityDuaCheckoutAction;
use App\Domains\Community\Actions\StartPaidCommunityDuaPurchaseAction;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Http\Requests\Community\StoreCommunityDuaRequest;
use App\Models\BillingPurchase;
use App\Models\StripePayment;
use App\Support\Seo\SeoPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class CommunityDuaController extends Controller
{
    public function create(): View
    {
        return view('community-dua.create', [
            'communityDuaPrice' => config('mydualist.billing.community_dua_price', '10.00'),
            'currency' => strtoupper((string) config('mydualist.billing.premium_currency', 'gbp')),
            'seo' => SeoPresenter::forRoute(
                'community-dua.create',
                'Submit a Community Dua',
                'Share a community dua request with pilgrims on My Dua List.',
            ),
        ]);
    }

    public function storeFree(
        StoreCommunityDuaRequest $request,
        CreateFreeCommunityDuaAction $action,
    ): RedirectResponse {
        $action($request->validated());

        return redirect()
            ->route('community-dua.create')
            ->with('status', 'Your community dua has been submitted to the free queue.');
    }

    public function checkout(
        StoreCommunityDuaRequest $request,
        StartPaidCommunityDuaPurchaseAction $action,
    ): RedirectResponse {
        try {
            $result = $action($request->validated(), Auth::user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('billing.purchases.checkout', $result['purchase']);
    }

    public function success(
        Request $request,
        StripeCheckoutService $stripe,
        FulfillPaidCommunityDuaCheckoutAction $fulfill,
    ): View|RedirectResponse {
        $purchaseId = (int) $request->query('purchase_id');

        if ($purchaseId > 0) {
            $purchase = BillingPurchase::query()
                ->with('communityDua')
                ->find($purchaseId);

            return view('community-dua.success', [
                'payment' => null,
                'purchase' => $purchase,
                'communityDua' => $purchase?->communityDua,
            ]);
        }

        $sessionId = (string) $request->query('session_id');

        if ($sessionId !== '') {
            $session = $stripe->retrieveCheckoutSession($sessionId);
            $fulfill($session);
        }

        $payment = $sessionId !== ''
            ? StripePayment::query()->where('stripe_checkout_session_id', $sessionId)->first()
            : null;

        return view('community-dua.success', [
            'payment' => $payment,
            'purchase' => null,
            'communityDua' => null,
        ]);
    }
}
