<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicSubmissionRequest extends FormRequest
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
            'first_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_anonymous' => ['nullable', 'boolean'],
            'content' => ['nullable', 'required_without:duas', 'string', 'min:3', 'max:1500'],
            'duas' => ['nullable', 'array', 'min:1', 'max:35'],
            'duas.*' => ['required', 'string', 'min:3', 'max:1500'],
            'note' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }
}
