<?php

namespace App\Http\Resources\Api\V1\Public;

use App\Http\Resources\Api\V1\ApiResource;
use App\Models\DuaSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PublicSubmissionResultResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, DuaSubmission> $submissions */
        $submissions = $this->resource;
        $count = $submissions->count();

        return [
            'count' => $count,
            'message' => $count === 1
                ? 'Your dua request has been submitted.'
                : "Your {$count} dua requests have been submitted.",
            'submissions' => $submissions->map(fn ($submission): array => [
                'id' => $submission->id,
                'status' => $submission->status->value,
            ])->values()->all(),
        ];
    }
}
