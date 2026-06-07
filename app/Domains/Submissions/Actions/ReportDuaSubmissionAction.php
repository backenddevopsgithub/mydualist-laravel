<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;

class ReportDuaSubmissionAction extends Action
{
    public function __construct(
        private readonly TransitionDuaSubmissionStatusAction $transitionAction,
    ) {}

    /**
     * @param  array{report_reason: string, report_note?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];
        $data = $args[1];

        ($this->transitionAction)($submission, DuaSubmissionStatus::Reported);

        $submission->forceFill([
            'report_reason' => $data['report_reason'],
            'report_note' => $data['report_note'] ?? null,
        ])->save();

        return $submission->fresh();
    }
}
