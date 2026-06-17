<?php

namespace App\Http\Requests\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class VerifySubmissionOtpRequest extends FormRequest
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
            'whatsapp_country_code' => ['required', 'string', 'max:6'],
            'whatsapp_phone' => ['required', 'string', 'max:20'],
            'otp' => ['required', 'string', 'max:6'],
        ];
    }
}
