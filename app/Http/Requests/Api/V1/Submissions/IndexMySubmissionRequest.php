<?php

namespace App\Http\Requests\Api\V1\Submissions;

use Illuminate\Foundation\Http\FormRequest;

class IndexMySubmissionRequest extends FormRequest
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
        $maxPerPage = (int) config('mydualist.defaults.pagination.max_per_page', 100);

        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$maxPerPage],
        ];
    }

    public function perPage(): int
    {
        return $this->integer(
            'per_page',
            (int) config('mydualist.defaults.pagination.per_page', 15),
        );
    }
}
