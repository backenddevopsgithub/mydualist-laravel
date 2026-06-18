<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionModerationAction;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;
use App\Models\User;

class HideReportedDuaSubmissionAction extends Action
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

        ($this->transitionAction)($submission, DuaSubmissionStatus::Hidden);

        return ($this->recordModerationAction)(
            $submission->fresh(),
            DuaSubmissionModerationAction::Hide,
            $previousStatus,
            DuaSubmissionStatus::Hidden,
            $moderator,
            $notes,
        );
    }
}
