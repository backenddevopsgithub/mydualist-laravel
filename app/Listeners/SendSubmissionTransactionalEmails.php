<?php

namespace App\Listeners;

use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Events\DuaSubmissionsCreated;

class SendSubmissionTransactionalEmails
{
    public function __construct(
        private readonly TransactionalEmailService $emails,
    ) {}

    public function handle(DuaSubmissionsCreated $event): void
    {
        $this->emails->handleSubmissionsCreated(
            $event->duaList,
            $event->submissions,
            $event->nonPersonalCountBefore,
        );
    }
}
