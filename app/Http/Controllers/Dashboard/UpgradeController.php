<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function __invoke(UserEntitlementService $entitlements): View
    {
        $user = Auth::user();
        $listLimit = $entitlements->activeListLimit($user);

        return view('dashboard.upgrade', [
            'user' => $user,
            'currentPlan' => $entitlements->planName($user),
            'hasPremium' => $entitlements->hasPremium($user),
            'activeListsCount' => $entitlements->activeListCount($user),
            'listLimit' => $listLimit,
            'remainingListSlots' => $entitlements->remainingListSlots($user),
            'visibleSubmissionLimit' => $listLimit === null ? null : (int) config('mydualist.billing.free_visible_submissions_per_list', 25),
            'premiumPrice' => config('mydualist.billing.premium_price', '11.99'),
            'premiumCurrency' => strtoupper((string) config('mydualist.billing.premium_currency', 'usd')),
        ]);
    }
}
