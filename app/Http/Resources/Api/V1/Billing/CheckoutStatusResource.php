<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

class CheckoutStatusResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{session_id: string, payment_status: string|null, status: string, has_premium: bool, fulfilled: bool} $payload */
        $payload = $this->resource;

        return [
            'session_id' => $payload['session_id'],
            'payment_status' => $payload['payment_status'],
            'status' => $payload['status'],
            'has_premium' => $payload['has_premium'],
            'fulfilled' => $payload['fulfilled'],
        ];
    }
}
