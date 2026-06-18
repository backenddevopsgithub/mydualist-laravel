<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionModerationAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;

class AutoHideReportedDuaSubmissionAction extends Action
{
    public function __construct(
        private readonly TransitionDuaSubmissionStatusAction $transitionAction,
        private readonly RecordDuaSubmissionModerationAction $recordModerationAction,
    ) {}

    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];

        if ($submission->status === DuaSubmissionStatus::Hidden) {
            return $submission;
        }

        $previousStatus = $submission->status;

        ($this->transitionAction)($submission, DuaSubmissionStatus::Hidden);

        return ($this->recordModerationAction)(
            $submission->fresh(),
            DuaSubmissionModerationAction::AutoHide,
            $previousStatus,
            DuaSubmissionStatus::Hidden,
            null,
            'Automatically hidden after report threshold was reached.',
        );
    }
}
