<?php

namespace App\Domains\Billing\Services;

use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\PaymentIntent;

class PurchaseReconciliationService extends Service
{
    public function __construct(
        private readonly StripePaymentIntentService $paymentIntents,
        private readonly PurchaseFulfillmentService $fulfillment,
    ) {}

    /**
     * @return array{checked: int, fulfilled: int, updated: int, failures: list<string>}
     */
    public function reconcile(?int $purchaseId = null, bool $dryRun = false): array
    {
        $result = [
            'checked' => 0,
            'fulfilled' => 0,
            'updated' => 0,
            'failures' => [],
        ];

        $this->purchasesNeedingReconciliation($purchaseId)->each(function (BillingPurchase $purchase) use (&$result, $dryRun): void {
            $result['checked']++;

            try {
                $outcome = $this->reconcilePurchase($purchase, $dryRun);
                $result['updated'] += $outcome['updated'] ? 1 : 0;
                $result['fulfilled'] += $outcome['fulfilled'] ? 1 : 0;
            } catch (RuntimeException $exception) {
                $result['failures'][] = "Purchase {$purchase->id}: {$exception->getMessage()}";
            }
        });

        return $result;
    }

    /**
     * @return Collection<int, BillingPurchase>
     */
    public function purchasesNeedingReconciliation(?int $purchaseId = null): Collection
    {
        $query = BillingPurchase::query()
            ->with(['product'])
            ->whereNotNull('payment_intent_id')
            ->where(function ($builder): void {
                $builder
                    ->where(function ($unfulfilled): void {
                        $unfulfilled
                            ->where('status', BillingPurchaseStatus::Succeeded)
                            ->whereNull('fulfilled_at');
                    })
                    ->orWhereIn('status', [
                        BillingPurchaseStatus::Processing,
                        BillingPurchaseStatus::RequiresConfirmation,
                    ]);
            });

        if ($purchaseId !== null) {
            $query->whereKey($purchaseId);
        }

        return $query->orderBy('id')->get();
    }

    /**
     * @return array{updated: bool, fulfilled: bool}
     */
    private function reconcilePurchase(BillingPurchase $purchase, bool $dryRun): array
    {
        $intent = $this->paymentIntents->retrieveIntent((string) $purchase->payment_intent_id);
        $stripeStatus = (string) $intent->status;
        $mappedStatus = $this->mapStripeStatus($stripeStatus);
        $fulfilled = false;
        $updated = false;

        if ($dryRun) {
            return [
                'updated' => $mappedStatus !== $purchase->status,
                'fulfilled' => $mappedStatus === BillingPurchaseStatus::Succeeded && $purchase->fulfilled_at === null,
            ];
        }

        DB::transaction(function () use ($purchase, $mappedStatus, $stripeStatus, &$fulfilled, &$updated): void {
            $locked = BillingPurchase::query()->whereKey($purchase->id)->lockForUpdate()->first();

            if (! $locked) {
                return;
            }

            if ($mappedStatus !== $locked->status) {
                $locked->update(['status' => $mappedStatus]);
                $updated = true;
            }

            $this->recordReconcileEvent($locked, $stripeStatus);

            if ($mappedStatus === BillingPurchaseStatus::Succeeded && $locked->fulfilled_at === null) {
                $this->fulfillment->fulfill($locked->fresh(['product', 'user']));
                $fulfilled = true;
            }
        });

        return compact('updated', 'fulfilled');
    }

    private function mapStripeStatus(string $stripeStatus): BillingPurchaseStatus
    {
        return match ($stripeStatus) {
            'succeeded' => BillingPurchaseStatus::Succeeded,
            'processing' => BillingPurchaseStatus::Processing,
            'requires_confirmation' => BillingPurchaseStatus::RequiresConfirmation,
            'requires_payment_method' => BillingPurchaseStatus::RequiresPaymentMethod,
            'canceled' => BillingPurchaseStatus::Canceled,
            default => BillingPurchaseStatus::Failed,
        };
    }

    private function recordReconcileEvent(BillingPurchase $purchase, string $stripeStatus): void
    {
        $idempotencyKey = 'reconcile:'.$purchase->id.':'.now()->format('Y-m-d-H');

        BillingPurchaseEvent::query()->firstOrCreate(
            [
                'billing_purchase_id' => $purchase->id,
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'event_type' => BillingPurchaseEventType::ReconcileAttempt,
                'payload' => [
                    'purchase_id' => $purchase->id,
                    'stripe_status' => $stripeStatus,
                    'purchase_status' => $purchase->status->value,
                ],
                'processed_at' => now(),
            ],
        );
    }
}
