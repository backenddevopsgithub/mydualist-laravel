<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DownloadSubmissionsRequest extends FormRequest
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
        ];
    }
}
