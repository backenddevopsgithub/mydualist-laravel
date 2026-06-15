<?php

namespace App\Console\Commands;

use App\Support\SchedulerHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SchedulerHeartbeatCommand extends Command
{
    protected $signature = 'scheduler:heartbeat';

    protected $description = 'Record a scheduler heartbeat timestamp for health monitoring';

    public function handle(): int
    {
        Cache::forever(SchedulerHealth::CACHE_KEY, now());

        Log::info('Laravel scheduler heartbeat updated.');

        return self::SUCCESS;
    }
}
