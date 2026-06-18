<?php

namespace App\Domains\Billing\Actions;

use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use RuntimeException;

class RetryBillingPurchaseFulfillmentAction
{
    public function __construct(
        private readonly PurchaseFulfillmentService $fulfillment,
    ) {}

    public function __invoke(BillingPurchase $purchase): BillingPurchase
    {
        $purchase = $purchase->fresh(['product', 'user']);

        if ($purchase->status !== BillingPurchaseStatus::Succeeded) {
            throw new RuntimeException('Only succeeded purchases can be fulfilled.');
        }

        if ($purchase->fulfilled_at !== null) {
            throw new RuntimeException('Purchase is already fulfilled.');
        }

        $this->fulfillment->fulfill($purchase);

        $purchase->refresh();

        if ($purchase->fulfilled_at === null) {
            throw new RuntimeException('Fulfillment did not complete. Check the product code and purchase prerequisites.');
        }

        return $purchase;
    }
}
