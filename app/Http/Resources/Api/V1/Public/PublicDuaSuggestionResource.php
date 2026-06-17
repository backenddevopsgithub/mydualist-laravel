<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Http\Resources\Api\V1\ApiResource;
use App\Models\DuaSuggestion;
use Illuminate\Http\Request;

/** @mixin DuaSuggestion */
class PublicDuaSuggestionResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'content' => $this->content,
            'source_type' => $this->source_type,
            'source_reference' => $this->source_reference,
            'sort_order' => $this->sort_order,
            'used_count' => $this->used_count,
        ];
    }
}
