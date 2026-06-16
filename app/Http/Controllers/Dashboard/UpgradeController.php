<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Billing\Support\BillingProductMapper;
use App\Enums\BillingProductCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function __invoke(Request $request, UserEntitlementService $entitlements): View
    {
        $user = Auth::user();
        $listLimit = $entitlements->activeListLimit($user);
        $selectedProduct = $this->resolveSelectedProduct($request->string('product')->toString());
        $selectedDuaListId = $request->integer('dua_list_id') ?: null;

        return view('dashboard.upgrade', [
            'user' => $user,
            'currentPlan' => $entitlements->planName($user),
            'hasPremium' => $entitlements->hasPremium($user),
            'activeListsCount' => $entitlements->activeListCount($user),
            'listLimit' => $listLimit,
            'remainingListSlots' => $entitlements->remainingListSlots($user),
            'visibleSubmissionLimit' => $listLimit === null ? null : (int) config('mydualist.billing.free_visible_submissions_per_list', 25),
            'selectedProduct' => $selectedProduct?->value,
            'selectedDuaListId' => $selectedDuaListId,
            'plans' => $this->plans(),
            'paymentSucceeded' => $request->string('status')->toString() === 'paid',
        ]);
    }

    /**
     * @return list<array{
     *     id: string,
     *     product_code: string,
     *     name: string,
     *     price: string,
     *     description: string,
     *     features: list<string>,
     *     hasListSelect?: bool,
     *     featured?: bool
     * }>
     */
    private function plans(): array
    {
        return [
            [
                'id' => 'request_pack_25',
                'product_code' => BillingProductCode::RequestPack25->value,
                'name' => '25 More Dua Requests',
                'price' => '£2.00',
                'description' => 'Unlock 25 more visible dua requests on one list.',
                'features' => ['Adds 25 visible submissions', 'Works on one list at a time', 'Stackable on the same list'],
                'hasListSelect' => true,
            ],
            [
                'id' => 'additional_list',
                'product_code' => BillingProductCode::AdditionalList->value,
                'name' => 'One Additional List',
                'price' => '£7.99',
                'description' => 'Adds one extra list with unlimited dua requests.',
                'features' => ['1 additional active list', 'Unlimited dua requests on that list', 'Premium support'],
            ],
            [
                'id' => 'unlimited_one_list',
                'product_code' => BillingProductCode::UnlimitedOneList->value,
                'name' => 'Unlimited One List',
                'price' => '£7.99',
                'description' => 'Unlock unlimited dua requests on one existing list.',
                'features' => ['Unlimited requests on one list', 'Choose which list to upgrade', 'Premium support'],
                'hasListSelect' => true,
            ],
            [
                'id' => 'unlimited_forever',
                'product_code' => BillingProductCode::UnlimitedForever->value,
                'name' => 'Unlimited Forever',
                'price' => '£11.99',
                'description' => 'Unlimited requests and unlimited lists for life.',
                'features' => ['Unlimited dua requests', 'Unlimited dua lists', 'Ad-free experience', 'Premium support'],
                'featured' => true,
            ],
        ];
    }

    private function resolveSelectedProduct(string $product): ?BillingProductCode
    {
        if ($product === '') {
            return null;
        }

        return BillingProductMapper::productCodeFromPlan($product)
            ?? BillingProductCode::tryFrom(strtoupper($product));
    }
}
