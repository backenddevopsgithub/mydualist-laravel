<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'whatsapp_notifications' => ['nullable', 'boolean'],
            'whatsapp_country_code' => ['nullable', 'required_if:whatsapp_notifications,1', 'string', 'max:6'],
            'whatsapp_phone' => ['nullable', 'required_if:whatsapp_notifications,1', 'string', 'max:20'],
            'whatsapp_verified' => ['nullable', 'boolean'],
            'terms' => ['accepted'],
            'content' => ['nullable', 'required_without:duas', 'string', 'min:3', 'max:1500'],
            'duas' => ['nullable', 'array', 'min:1', 'max:35'],
            'duas.*' => ['required', 'string', 'min:3', 'max:1500'],
            'website' => ['nullable', 'string', 'max:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'duas.*.max' => 'Each dua must be no more than 1,500 characters.',
            'duas.*.min' => 'Each dua must be at least 3 characters.',
            'duas.*.required' => 'Please enter your dua text.',
            'terms.accepted' => 'You must agree to the terms and privacy policy.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'first_name' => 'first name',
            'last_name' => 'last name',
            'email' => 'email address',
            'gender' => 'gender',
        ];

        foreach ($this->input('duas', []) as $index => $value) {
            $attributes['duas.'.$index] = 'Dua '.($index + 1);
        }

        return $attributes;
    }
}
