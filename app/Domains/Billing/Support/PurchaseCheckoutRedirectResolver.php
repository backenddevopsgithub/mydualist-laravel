<?php

namespace App\Domains\Billing\Support;

use App\Enums\BillingProductCode;
use App\Models\BillingPurchase;

class PurchaseCheckoutRedirectResolver
{
    public function successUrl(BillingPurchase $purchase): string
    {
        $metadata = $purchase->metadata ?? [];

        if (! empty($metadata['success_url'])) {
            return (string) $metadata['success_url'];
        }

        $productCode = BillingProductCode::tryFrom((string) optional($purchase->product)->code);

        return match ($productCode) {
            BillingProductCode::RequestPack25,
            BillingProductCode::UnlimitedOneList => $this->listShowUrl($purchase, ['payment' => 'success'])
                ?? route('dashboard.upgrade', ['status' => 'paid']),
            BillingProductCode::CommunityDuaPaid => route('community-dua.success', [
                'purchase_id' => $purchase->id,
            ]),
            default => route('dashboard.upgrade', ['status' => 'paid']),
        };
    }

    public function failureUrl(BillingPurchase $purchase): string
    {
        $metadata = $purchase->metadata ?? [];

        if (! empty($metadata['failure_url'])) {
            return (string) $metadata['failure_url'];
        }

        $productCode = BillingProductCode::tryFrom((string) optional($purchase->product)->code);

        return match ($productCode) {
            BillingProductCode::CommunityDuaPaid => route('community-dua.create'),
            BillingProductCode::RequestPack25,
            BillingProductCode::UnlimitedOneList => $this->listShowUrl($purchase)
                ?? route('dashboard.upgrade'),
            default => route('dashboard.upgrade'),
        };
    }

    public function continueLabel(BillingPurchase $purchase): string
    {
        $productCode = BillingProductCode::tryFrom((string) optional($purchase->product)->code);

        return match ($productCode) {
            BillingProductCode::RequestPack25 => 'Back to your list',
            BillingProductCode::UnlimitedOneList => 'View upgraded list',
            BillingProductCode::CommunityDuaPaid => 'View submission status',
            BillingProductCode::AdditionalList => 'Create another list',
            default => 'Continue',
        };
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function listShowUrl(BillingPurchase $purchase, array $query = []): ?string
    {
        $duaList = $purchase->relationLoaded('duaList')
            ? $purchase->duaList
            : $purchase->loadMissing('duaList')->duaList;

        if ($duaList === null) {
            return null;
        }

        return route('dashboard.lists.show', array_merge(['duaList' => $duaList], $query));
    }
}
