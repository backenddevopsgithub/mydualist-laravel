<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Events\DuaSubmissionCompleted;
use App\Models\DuaSubmission;

class TransitionDuaSubmissionStatusAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];
        /** @var DuaSubmissionStatus $status */
        $status = $args[1];
        $wasCompleted = $submission->isCompleted();

        $timestamps = match ($status) {
            DuaSubmissionStatus::Pending => [
                'completed_at' => null,
                'completion_notified_at' => null,
                'hidden_at' => null,
                'archived_at' => null,
                'reported_at' => null,
            ],
            DuaSubmissionStatus::Completed => [
                'completed_at' => now(),
                'hidden_at' => null,
                'archived_at' => null,
            ],
            DuaSubmissionStatus::Hidden => [
                'hidden_at' => now(),
            ],
            DuaSubmissionStatus::Archived => [
                'archived_at' => now(),
            ],
            DuaSubmissionStatus::Reported => [
                'reported_at' => now(),
            ],
        };

        $submission->forceFill([
            'status' => $status,
            ...$timestamps,
        ])->save();

        if ($status === DuaSubmissionStatus::Completed && ! $wasCompleted) {
            event(new DuaSubmissionCompleted($submission->fresh()));
        }

        return $submission;
    }
}
