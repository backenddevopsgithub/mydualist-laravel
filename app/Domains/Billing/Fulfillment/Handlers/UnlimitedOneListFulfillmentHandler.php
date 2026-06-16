<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Enums\BillingProductCode;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\EntitlementGrant;

class UnlimitedOneListFulfillmentHandler extends AbstractPurchaseFulfillmentHandler
{
    public function supports(BillingProductCode $productCode): bool
    {
        return $productCode === BillingProductCode::UnlimitedOneList;
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        $this->requireUser($purchase);
        $this->requireList($purchase);

        $this->grantUniqueListEntitlement($purchase, [
            'dua_list_id' => $purchase->dua_list_id,
            'entitlement_key' => EntitlementKey::ListUnlimitedOverride,
            'quantity' => 1,
            'is_stackable' => false,
            'dedupe_key' => EntitlementGrant::dedupeKeyForListGrant(
                (int) $purchase->dua_list_id,
                EntitlementKey::ListUnlimitedOverride,
            ),
        ]);

        $this->unlocker->unlockEligibleForList($purchase, (int) $purchase->dua_list_id);
    }
}
