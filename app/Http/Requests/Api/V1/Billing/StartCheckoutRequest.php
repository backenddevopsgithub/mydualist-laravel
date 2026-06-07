<?php

namespace App\Http\Requests\Api\V1\Billing;

use Illuminate\Foundation\Http\FormRequest;

class StartCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $redirectUrl = ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/.+|[a-z][a-z0-9+\-.]*:\/\/.+)/i'];

        return [
            'success_url' => $redirectUrl,
            'cancel_url' => $redirectUrl,
        ];
    }

    /**
     * @return array{success_url?: string|null, cancel_url?: string|null}
     */
    public function checkoutOptions(): array
    {
        return array_filter([
            'success_url' => $this->input('success_url'),
            'cancel_url' => $this->input('cancel_url'),
        ], fn (mixed $value): bool => $value !== null);
    }
}
