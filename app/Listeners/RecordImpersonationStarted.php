<?php

namespace App\Listeners;

use App\Domains\Auth\Services\ImpersonationLogService;
use App\Models\User;
use Lab404\Impersonate\Events\TakeImpersonation;

class RecordImpersonationStarted
{
    public function __construct(
        private readonly ImpersonationLogService $logs,
    ) {}

    public function handle(TakeImpersonation $event): void
    {
        if (! $event->impersonator instanceof User || ! $event->impersonated instanceof User) {
            return;
        }

        $this->logs->recordStarted(
            $event->impersonator,
            $event->impersonated,
            request()->ip(),
        );
    }
}
