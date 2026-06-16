<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Enums\BillingProductCode;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;

class RequestPack25FulfillmentHandler extends AbstractPurchaseFulfillmentHandler
{
    public function supports(BillingProductCode $productCode): bool
    {
        return $productCode === BillingProductCode::RequestPack25;
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        $this->requireUser($purchase);
        $this->requireList($purchase);

        $packSize = (int) config('billing.request_pack_size');

        $this->grantFromPurchase($purchase, [
            'dua_list_id' => $purchase->dua_list_id,
            'entitlement_key' => EntitlementKey::ListVisibleSubmissionPack,
            'quantity' => $packSize,
            'is_stackable' => true,
        ]);

        $this->unlocker->unlockEligibleForList(
            $purchase,
            (int) $purchase->dua_list_id,
            (int) config('billing.request_pack_unlock_batch'),
        );
    }
}
