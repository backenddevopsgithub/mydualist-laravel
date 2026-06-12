<?php

namespace App\Http\Resources\Api\V1\Submissions;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Http\Resources\Api\V1\ApiResource;
use App\Models\DuaSubmission;
use Illuminate\Http\Request;

/** @mixin DuaSubmission */
class DuaSubmissionResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        if ($user !== null && ! app(UserEntitlementService::class)->canViewSubmission($user, $this->resource)) {
            return [
                'id' => $this->id,
                'status' => $this->status->value,
                'is_personal_dua' => $this->is_personal_dua,
                'locked' => true,
            ];
        }

        return [
            'id' => $this->id,
            'dua_list_id' => $this->dua_list_id,
            'dua_list' => $this->whenLoaded('duaList', fn (): array => [
                'id' => $this->duaList->id,
                'title' => $this->duaList->title,
                'slug' => $this->duaList->slug,
            ]),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->displayName(),
            'email' => $this->email,
            'is_anonymous' => $this->is_anonymous,
            'is_personal_dua' => $this->is_personal_dua,
            'content' => $this->content,
            'note' => $this->note,
            'status' => $this->status->value,
            'report_reason' => $this->report_reason,
            'report_note' => $this->when($this->report_reason !== null, $this->report_note),
            'locked' => false,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'hidden_at' => $this->hidden_at?->toIso8601String(),
            'archived_at' => $this->archived_at?->toIso8601String(),
            'reported_at' => $this->reported_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
