<?php

namespace App\Listeners;

use App\Domains\Auth\Services\ImpersonationLogService;
use App\Models\User;
use Lab404\Impersonate\Events\LeaveImpersonation;

class RecordImpersonationEnded
{
    public function __construct(
        private readonly ImpersonationLogService $logs,
    ) {}

    public function handle(LeaveImpersonation $event): void
    {
        if (! $event->impersonator instanceof User || ! $event->impersonated instanceof User) {
            return;
        }

        $this->logs->recordEnded($event->impersonator, $event->impersonated);
    }
}
