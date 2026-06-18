<?php

namespace App\Domains\Submissions\Actions;

use App\Actions\Action;
use App\Enums\DuaSubmissionStatus;
use App\Models\DuaSubmission;

class ReportDuaSubmissionAction extends Action
{
    public function __construct(
        private readonly TransitionDuaSubmissionStatusAction $transitionAction,
        private readonly AutoHideReportedDuaSubmissionAction $autoHideAction,
    ) {}

    /**
     * @param  array{report_reason: string, report_note?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaSubmission $submission */
        $submission = $args[0];
        $data = $args[1];

        $isNewReport = $submission->reported_at === null;

        if ($isNewReport) {
            $submission->forceFill([
                'status_before_report' => $submission->status->value,
                'report_count' => 1,
            ])->save();

            ($this->transitionAction)($submission->fresh(), DuaSubmissionStatus::Reported);
        } else {
            $submission->forceFill([
                'report_count' => $submission->report_count + 1,
            ])->save();
        }

        $submission->forceFill([
            'report_reason' => $data['report_reason'],
            'report_note' => $data['report_note'] ?? null,
        ])->save();

        $submission = $submission->fresh();

        $this->maybeAutoHide($submission);

        return $submission;
    }

    private function maybeAutoHide(DuaSubmission $submission): void
    {
        $threshold = config('mydualist.moderation.auto_hide_threshold');

        if ($threshold === null || $threshold < 1) {
            return;
        }

        if ($submission->report_count < $threshold) {
            return;
        }

        ($this->autoHideAction)($submission);
    }
}
