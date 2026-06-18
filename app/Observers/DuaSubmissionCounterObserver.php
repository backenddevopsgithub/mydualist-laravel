<?php

namespace App\Observers;

use App\Models\DuaSubmission;
use App\Services\SubmissionCounterService;

class DuaSubmissionCounterObserver
{
    public function __construct(
        private readonly SubmissionCounterService $counters,
    ) {}

    public function created(DuaSubmission $submission): void
    {
        if (SubmissionCounterService::isDisabled()) {
            return;
        }

        $this->counters->recordCreated($submission);
    }

    public function updated(DuaSubmission $submission): void
    {
        if (SubmissionCounterService::isDisabled()) {
            return;
        }

        $this->counters->recordUpdated($submission);
    }

    public function deleted(DuaSubmission $submission): void
    {
        if (SubmissionCounterService::isDisabled()) {
            return;
        }

        $this->counters->recordRemoved($submission);
    }

    public function restored(DuaSubmission $submission): void
    {
        if (SubmissionCounterService::isDisabled()) {
            return;
        }

        $this->counters->recordCreated($submission);
    }
}
