<?php

namespace App\Listeners;

use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Events\DuaSubmissionCompleted;

class SendDuaCompletedEmail
{
    public function __construct(
        private readonly TransactionalEmailService $emails,
    ) {}

    public function handle(DuaSubmissionCompleted $event): void
    {
        $this->emails->sendCompletionIfNeeded($event->submission);
    }
}
