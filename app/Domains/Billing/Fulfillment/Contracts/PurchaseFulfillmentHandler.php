<?php

namespace App\Domains\Billing\Fulfillment\Contracts;

use App\Enums\BillingProductCode;
use App\Models\BillingPurchase;

interface PurchaseFulfillmentHandler
{
    public function supports(BillingProductCode $productCode): bool;

    public function fulfill(BillingPurchase $purchase): void;
}
