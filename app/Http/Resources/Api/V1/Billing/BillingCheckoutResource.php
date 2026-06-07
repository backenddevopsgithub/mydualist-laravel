<?php

namespace App\Http\Resources\Api\V1\Billing;

use App\Http\Resources\Api\V1\ApiResource;
use Illuminate\Http\Request;

class BillingCheckoutResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{session: array{id: string, url: string, amount_total: int|null, currency: string|null}} $payload */
        $payload = $this->resource;
        $session = $payload['session'];

        return [
            'session_id' => $session['id'],
            'checkout_url' => $session['url'],
            'amount_total' => $session['amount_total'],
            'currency' => $session['currency'],
        ];
    }
}
