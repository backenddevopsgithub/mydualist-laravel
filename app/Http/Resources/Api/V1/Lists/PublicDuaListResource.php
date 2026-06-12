<?php

namespace App\Http\Resources\Api\V1\Lists;

use App\Models\DuaList;
use Illuminate\Http\Request;

/** @mixin DuaList */
class PublicDuaListResource extends DuaListResource
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
            'submissions_count' => $this->submissions_count ?? $this->submissions()->count(),
            'completed_submissions_count' => $this->completed_submissions_count ?? 0,
            'owner' => [
                'name' => $this->user?->name,
            ],
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
