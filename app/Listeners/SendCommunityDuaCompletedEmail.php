<?php

namespace App\Listeners;

use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Events\CommunityDuaCompletedByPilgrim;

class SendCommunityDuaCompletedEmail
{
    public function __construct(
        private readonly TransactionalEmailService $emails,
    ) {}

    public function handle(CommunityDuaCompletedByPilgrim $event): void
    {
        $this->emails->sendCommunityDuaCompletion($event->communityDua, $event->completedBy);
    }
}
