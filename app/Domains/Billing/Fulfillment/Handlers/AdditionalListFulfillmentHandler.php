<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Enums\BillingProductCode;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;

class AdditionalListFulfillmentHandler extends AbstractPurchaseFulfillmentHandler
{
    public function supports(BillingProductCode $productCode): bool
    {
        return $productCode === BillingProductCode::AdditionalList;
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        $this->requireUser($purchase);

        $this->grantFromPurchase($purchase, [
            'entitlement_key' => EntitlementKey::UserExtraListSlot,
            'quantity' => 1,
            'is_stackable' => true,
        ]);
    }
}
