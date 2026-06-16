<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Enums\BillingProductCode;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\EntitlementGrant;
use App\Models\User;

class UnlimitedForeverFulfillmentHandler extends AbstractPurchaseFulfillmentHandler
{
    public function supports(BillingProductCode $productCode): bool
    {
        return $productCode === BillingProductCode::UnlimitedForever;
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        $this->requireUser($purchase);

        $this->grantUniqueUserEntitlement($purchase, [
            'entitlement_key' => EntitlementKey::UserUnlimitedForever,
            'quantity' => 1,
            'is_stackable' => false,
            'dedupe_key' => EntitlementGrant::dedupeKeyForUserGrant(
                (int) $purchase->user_id,
                EntitlementKey::UserUnlimitedForever,
            ),
        ]);

        $user = User::query()->findOrFail($purchase->user_id);

        $this->unlocker->unlockEligibleForUserLists($purchase, $user);
    }
}
