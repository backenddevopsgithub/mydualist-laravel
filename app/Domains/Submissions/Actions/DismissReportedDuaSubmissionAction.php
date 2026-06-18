<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionModerationAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;
use App\Models\User;

class DismissReportedDuaSubmissionAction extends Action
{
    public function __construct(
        private readonly TransitionDuaSubmissionStatusAction $transitionAction,
        private readonly RecordDuaSubmissionModerationAction $recordModerationAction,
    ) {}

    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];
        /** @var User $moderator */
        $moderator = $args[1];
        /** @var string|null $notes */
        $notes = $args[2] ?? null;

        $previousStatus = $submission->status;
        $restoredStatus = $submission->status_before_report !== null
            ? DuaSubmissionStatus::from($submission->status_before_report)
            : DuaSubmissionStatus::Pending;

        $submission->forceFill([
            'report_reason' => null,
            'report_note' => null,
            'reported_at' => null,
            'report_count' => 0,
            'status_before_report' => null,
        ])->save();

        ($this->transitionAction)($submission->fresh(), $restoredStatus);

        return ($this->recordModerationAction)(
            $submission->fresh(),
            DuaSubmissionModerationAction::Dismiss,
            $previousStatus,
            $restoredStatus,
            $moderator,
            $notes,
        );
    }
}
