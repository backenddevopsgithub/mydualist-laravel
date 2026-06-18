<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionModerationAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;
use App\Models\DuaSubmissionModerationLog;
use App\Models\User;

class RecordDuaSubmissionModerationAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];
        /** @var DuaSubmissionModerationAction $action */
        $action = $args[1];
        /** @var DuaSubmissionStatus|null $previousStatus */
        $previousStatus = $args[2] ?? null;
        /** @var DuaSubmissionStatus|null $newStatus */
        $newStatus = $args[3] ?? null;
        /** @var User|null $moderator */
        $moderator = $args[4] ?? null;
        /** @var string|null $notes */
        $notes = $args[5] ?? null;

        DuaSubmissionModerationLog::query()->create([
            'dua_submission_id' => $submission->id,
            'moderator_id' => $moderator?->id,
            'action' => $action,
            'previous_status' => $previousStatus?->value,
            'new_status' => $newStatus?->value,
            'notes' => $notes,
            'created_at' => now(),
        ]);

        $submission->forceFill([
            'moderated_by' => $moderator?->id,
            'moderated_at' => now(),
            'moderation_action' => $action->value,
            'moderation_notes' => $notes,
        ])->save();

        return $submission->fresh();
    }
}
