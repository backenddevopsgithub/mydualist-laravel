<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

class PurchaseClientSecretResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{client_secret: string|null, payment_intent_id: string|null} $payload */
        $payload = $this->resource;

        return [
            'client_secret' => $payload['client_secret'],
            'payment_intent_id' => $payload['payment_intent_id'],
        ];
    }
}
