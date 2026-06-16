<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Http\Resources\Api\V1\ApiResource;
use App\Models\BillingPurchase;
use Illuminate\Http\Request;

class PurchaseResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BillingPurchase $purchase */
        $purchase = $this->resource;

        return [
            'id' => $purchase->id,
            'product_code' => $purchase->product?->code,
            'status' => $purchase->status->value,
            'amount_minor' => $purchase->amount_minor,
            'currency' => $purchase->currency,
            'idempotency_key' => $purchase->idempotency_key,
            'user_id' => $purchase->user_id,
            'dua_list_id' => $purchase->dua_list_id,
            'community_dua_id' => $purchase->community_dua_id,
            'payment_intent_id' => $purchase->payment_intent_id,
            'fulfilled_at' => $purchase->fulfilled_at?->toIso8601String(),
            'created_at' => $purchase->created_at?->toIso8601String(),
        ];
    }
}
