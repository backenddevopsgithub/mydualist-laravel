<?php

namespace App\Http\Requests\Support;

use App\Support\SupportTicketReasons;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
        return [
            'reason' => ['required', Rule::in(SupportTicketReasons::keys())],
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:60'],
            'surname' => ['required', 'string', 'max:60'],
            'comments' => ['required', 'string', 'min:5', 'max:3000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
