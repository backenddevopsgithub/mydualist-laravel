<?php

namespace App\Http\Requests\Community;

use App\Rules\MaxWords;
use App\Support\SubmissionGenders;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunityDuaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('gender')) {
            $this->merge([
                'gender' => SubmissionGenders::normalize($this->input('gender')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:15'],
            'last_name' => ['required', 'string', 'max:15'],
            'email' => ['required', 'email', 'max:255'],
            'gender' => ['required', 'string', Rule::in(SubmissionGenders::values())],
            'content' => ['required', 'string', 'min:3', new MaxWords(100)],
            'terms' => ['accepted'],
            'whatsapp_notifications' => ['nullable', 'boolean'],
            'whatsapp_country_code' => ['nullable', 'required_if:whatsapp_notifications,1', 'string', 'max:6'],
            'whatsapp_phone' => ['nullable', 'required_if:whatsapp_notifications,1', 'string', 'max:20'],
            'whatsapp_verification_token' => ['nullable', 'required_if:whatsapp_notifications,1', 'string', 'size:64'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must agree to the terms and privacy policy.',
        ];
    }
}
