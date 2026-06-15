<?php

namespace App\Listeners;

use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Events\UserEmailVerified;

class SendWelcomeAndPendingListEmails
{
    public function __construct(
        private readonly TransactionalEmailService $emails,
    ) {}

    public function handle(UserEmailVerified $event): void
    {
        $this->emails->sendWelcomeIfNeeded($event->user);
        $this->emails->sendPendingListCreatedEmails($event->user->fresh());
    }
}
