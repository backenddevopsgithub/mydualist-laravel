<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Fulfillment\Contracts\PurchaseFulfillmentHandler;
use App\Domains\Billing\Fulfillment\Handlers\AdditionalListFulfillmentHandler;
use App\Domains\Billing\Fulfillment\Handlers\CommunityDuaPaidFulfillmentHandler;
use App\Domains\Billing\Fulfillment\Handlers\RequestPack25FulfillmentHandler;
use App\Domains\Billing\Fulfillment\Handlers\UnlimitedForeverFulfillmentHandler;
use App\Domains\Billing\Fulfillment\Handlers\UnlimitedOneListFulfillmentHandler;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Services\Service;
use RuntimeException;

class PurchaseFulfillmentService extends Service
{
    /**
     * @var list<PurchaseFulfillmentHandler>
     */
    private array $handlers;

    public function __construct(
        RequestPack25FulfillmentHandler $requestPack25,
        UnlimitedOneListFulfillmentHandler $unlimitedOneList,
        AdditionalListFulfillmentHandler $additionalList,
        UnlimitedForeverFulfillmentHandler $unlimitedForever,
        CommunityDuaPaidFulfillmentHandler $communityDuaPaid,
    ) {
        $this->handlers = [
            $requestPack25,
            $unlimitedOneList,
            $additionalList,
            $unlimitedForever,
            $communityDuaPaid,
        ];
    }

    public function fulfill(BillingPurchase $purchase): void
    {
        $purchase = $purchase->fresh(['product', 'user']);

        if ($purchase->fulfilled_at !== null) {
            return;
        }

        if ($purchase->status !== BillingPurchaseStatus::Succeeded) {
            return;
        }

        $productCode = BillingProductCode::tryFrom((string) optional($purchase->product)->code);

        if ($productCode === null) {
            return;
        }

        $handler = $this->handlerFor($productCode);

        if ($handler === null) {
            return;
        }

        $this->recordFulfillmentEvent(
            $purchase,
            BillingPurchaseEventType::FulfillmentStarted,
            'fulfillment:started:'.$purchase->id,
        );

        $handler->fulfill($purchase);

        $purchase->forceFill(['fulfilled_at' => now()])->save();

        $this->recordFulfillmentEvent(
            $purchase,
            BillingPurchaseEventType::FulfillmentApplied,
            'fulfillment:applied:'.$purchase->id,
        );
    }

    private function handlerFor(BillingProductCode $productCode): ?PurchaseFulfillmentHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($productCode)) {
                return $handler;
            }
        }

        return null;
    }

    private function recordFulfillmentEvent(
        BillingPurchase $purchase,
        BillingPurchaseEventType $eventType,
        string $idempotencyKey,
    ): void {
        $created = BillingPurchaseEvent::query()->firstOrCreate(
            [
                'billing_purchase_id' => $purchase->id,
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'event_type' => $eventType,
                'processed_at' => now(),
                'payload' => [
                    'purchase_id' => $purchase->id,
                    'product_code' => $purchase->product?->code,
                ],
            ],
        );

        if (! $created->wasRecentlyCreated && $created->event_type !== $eventType) {
            throw new RuntimeException('Fulfillment event idempotency key conflict.');
        }
    }
}
