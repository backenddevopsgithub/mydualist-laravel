<?php

namespace App\Http\Requests\Api\V1\Submissions;

use App\Enums\DuaSubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSubmissionRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::in(DuaSubmissionStatus::values())],
            'search' => ['sometimes', 'string', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$maxPerPage],
        ];
    }

    /**
     * @return array{status: string, search: string}
     */
    public function filters(): array
    {
        return [
            'status' => $this->string('status')->toString() ?: DuaSubmissionStatus::Pending->value,
            'search' => trim($this->string('search')->toString()),
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
