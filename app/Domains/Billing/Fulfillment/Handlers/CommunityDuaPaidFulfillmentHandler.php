<?php

namespace App\Domains\Billing\Fulfillment\Handlers;

use App\Domains\Billing\Fulfillment\Contracts\PurchaseFulfillmentHandler;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Enums\BillingProductCode;
use App\Enums\CommunityDuaStatus;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use RuntimeException;

class CommunityDuaPaidFulfillmentHandler implements PurchaseFulfillmentHandler
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
    ) {}

    public function supports(BillingProductCode $productCode): bool
    {
        return $productCode === BillingProductCode::CommunityDuaPaid;
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        if ($purchase->community_dua_id === null) {
            throw new RuntimeException('Community dua purchase fulfillment requires a community dua target.');
        }

        /** @var CommunityDua $communityDua */
        $communityDua = CommunityDua::query()->findOrFail($purchase->community_dua_id);

        if ($communityDua->status !== CommunityDuaStatus::PendingPayment) {
            return;
        }

        $communityDua->forceFill([
            'status' => CommunityDuaStatus::Active,
            'is_visible' => true,
        ])->save();

        $this->queue->notifyWaitingUsersOfNewDua($communityDua->fresh());
    }
}
