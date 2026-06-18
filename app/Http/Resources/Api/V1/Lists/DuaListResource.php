<?php

namespace App\Http\Resources\Api\V1\Lists;

use App\Http\Resources\Api\V1\ApiResource;
use App\Models\DuaList;
use Illuminate\Http\Request;

/** @mixin DuaList */
class DuaListResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'occasion' => $this->occasion,
            'occasion_label' => $this->occasionLabel(),
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'days_remaining_label' => $this->daysRemainingLabel(),
            'public_url' => $this->publicUrl(),
            'accepts_submissions' => $this->acceptsSubmissions(),
            'closed_reason' => $this->closedReason(),
            'submissions_count' => (int) $this->submissions_count,
            'completed_submissions_count' => (int) $this->completed_submissions_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
