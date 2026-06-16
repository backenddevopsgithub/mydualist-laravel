<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

class PurchasePaymentStatusResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{
         *     status: string,
         *     is_payable: bool,
         *     is_completed: bool,
         *     fulfilled_at: string|null,
         *     failure_reason: string|null
         * } $payload
         */
        $payload = $this->resource;

        return [
            'status' => $payload['status'],
            'is_payable' => $payload['is_payable'],
            'is_completed' => $payload['is_completed'],
            'fulfilled_at' => $payload['fulfilled_at'],
            'failure_reason' => $payload['failure_reason'],
        ];
    }
}
