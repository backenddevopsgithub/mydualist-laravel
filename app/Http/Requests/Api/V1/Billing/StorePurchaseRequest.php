<?php

namespace App\Http\Requests\Api\V1\Billing;

use App\Enums\BillingProductCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_code' => ['required', 'string', Rule::in(BillingProductCode::values())],
            'idempotency_key' => ['required', 'string', 'max:191'],
            'dua_list_id' => ['nullable', 'integer', 'min:1'],
            'community_dua_id' => ['nullable', 'integer', 'min:1'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array{product_code: string, idempotency_key: string, dua_list_id?: int|null, community_dua_id?: int|null, metadata?: array<string, mixed>}
     */
    public function payload(): array
    {
        /** @var array{product_code: string, idempotency_key: string, dua_list_id?: int|null, community_dua_id?: int|null, metadata?: array<string, mixed>} $validated */
        $validated = $this->validated();

        return $validated;
    }
}
