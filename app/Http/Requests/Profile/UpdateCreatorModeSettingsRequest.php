<?php

namespace App\Http\Requests\Profile;

use App\Support\CreatorMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCreatorModeSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return CreatorMode::enabled() && $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'dua_list_id' => [
                'required',
                'integer',
                Rule::exists('dua_lists', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('list_mode', CreatorMode::MODE_CREATOR),
            ],
            'donation_link' => CreatorMode::donationLinkRules(required: false),
            'donation_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
