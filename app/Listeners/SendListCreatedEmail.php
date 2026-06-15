<?php

namespace App\Listeners;

use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Events\DuaListCreated;

class SendListCreatedEmail
{
    public function __construct(
        private readonly TransactionalEmailService $emails,
    ) {}

    public function handle(DuaListCreated $event): void
    {
        $this->emails->sendListCreatedIfNeeded($event->duaList);
    }
}
