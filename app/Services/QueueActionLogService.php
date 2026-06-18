<?php

namespace App\Services;

use App\Models\QueueActionLog;
use App\Models\User;

class QueueActionLogService extends Service
{
    /**
     * @param  list<int|string>  $jobIds
     * @param  array<string, mixed>  $metadata
     */
    public function record(User $user, string $action, array $jobIds = [], ?string $ipAddress = null, array $metadata = []): QueueActionLog
    {
        return QueueActionLog::query()->create([
            'user_id' => $user->id,
            'action' => $action,
            'job_ids' => array_values($jobIds),
            'ip_address' => $ipAddress,
            'metadata' => $metadata,
        ]);
    }
}
