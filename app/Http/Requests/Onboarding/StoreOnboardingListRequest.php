<?php

namespace App\Http\Requests\Onboarding;

use App\Http\Requests\Lists\StoreListRequest;

class StoreOnboardingListRequest extends StoreListRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
