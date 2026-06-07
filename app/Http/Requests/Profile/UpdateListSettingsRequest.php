<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListSettingsRequest extends FormRequest
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
            'dua_list_id' => [
                'required',
                Rule::exists('dua_lists', 'id')->where('user_id', $this->user()?->id),
            ],
            'dua_limit_per_person' => ['nullable', 'integer', 'between:1,5'],
            'display_order' => ['required', Rule::in(['date', 'gender', 'person'])],
            'email_frequency' => ['required', Rule::in(['every_submission', 'daily_summary'])],
        ];
    }
}
