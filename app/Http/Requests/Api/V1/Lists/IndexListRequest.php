<?php

namespace App\Http\Requests\Api\V1\Lists;

use App\Models\DuaList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexListRequest extends FormRequest
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
            'status' => ['sometimes', 'string', Rule::in([DuaList::STATUS_ACTIVE, DuaList::STATUS_ARCHIVED])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:'.$maxPerPage],
        ];
    }

    public function listStatus(): string
    {
        return $this->string('status')->toString() ?: DuaList::STATUS_ACTIVE;
    }

    public function perPage(): int
    {
        return $this->integer(
            'per_page',
            (int) config('mydualist.defaults.pagination.per_page', 15),
        );
    }
}
