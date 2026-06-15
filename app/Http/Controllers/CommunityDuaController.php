<?php

namespace App\Http\Controllers;

use App\Domains\Community\Actions\CreateFreeCommunityDuaAction;
use App\Domains\Community\Actions\StartPaidCommunityDuaCheckoutAction;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Domains\Community\Actions\FulfillPaidCommunityDuaCheckoutAction;
use App\Http\Requests\Community\StoreCommunityDuaRequest;
use App\Models\StripePayment;
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
        StartPaidCommunityDuaCheckoutAction $action,
    ): RedirectResponse {
        try {
            $checkout = $action($request->validated(), Auth::user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()])->withInput();
        }

        return redirect()->away($checkout['session']['url']);
    }

    public function success(
        Request $request,
        StripeCheckoutService $stripe,
        FulfillPaidCommunityDuaCheckoutAction $fulfill,
    ): View|RedirectResponse {
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
        ]);
    }
}
