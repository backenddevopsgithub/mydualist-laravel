<?php

namespace App\Domains\Auth\Services;

use App\Models\ImpersonationLog;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Carbon;

class ImpersonationLogService extends Service
{
    public function recordStarted(User $impersonator, User $impersonated, ?string $ipAddress): ImpersonationLog
    {
        return ImpersonationLog::query()->create([
            'impersonator_id' => $impersonator->id,
            'impersonated_user_id' => $impersonated->id,
            'started_at' => now(),
            'ip_address' => $ipAddress,
        ]);
    }

    public function recordEnded(User $impersonator, User $impersonated): ?ImpersonationLog
    {
        $log = ImpersonationLog::query()
            ->where('impersonator_id', $impersonator->id)
            ->where('impersonated_user_id', $impersonated->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        if ($log === null) {
            return null;
        }

        $log->forceFill(['ended_at' => Carbon::now()])->save();

        return $log->fresh();
    }
}
