<?php

namespace App\Services;

use App\Support\SchedulerHealth;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SystemHealthService extends Service
{
    public function schedulerLastRun(): ?CarbonInterface
    {
        $timestamp = cache()->get(SchedulerHealth::CACHE_KEY);

        if ($timestamp === null) {
            return null;
        }

        return Carbon::parse($timestamp);
    }

    public function schedulerStatus(): string
    {
        return SchedulerHealth::status($this->schedulerLastRun());
    }

    public function queueConnection(): string
    {
        return (string) config('queue.default');
    }

    public function pendingJobsCount(): int
    {
        $connection = $this->queueConnection();

        if ($connection === 'database') {
            return (int) DB::table(config('queue.connections.database.table', 'jobs'))->count();
        }

        if ($connection === 'redis') {
            $queue = (string) config('queue.connections.redis.queue', 'default');
            $redisConnection = (string) config('queue.connections.redis.connection', 'default');

            return (int) Redis::connection($redisConnection)->llen('queues:'.$queue);
        }

        return 0;
    }

    public function failedJobsCount(): int
    {
        return (int) DB::table(config('queue.failed.table', 'failed_jobs'))->count();
    }

    public function lastFailedJobAt(): ?CarbonInterface
    {
        $failedAt = DB::table(config('queue.failed.table', 'failed_jobs'))
            ->max('failed_at');

        if ($failedAt === null) {
            return null;
        }

        return Carbon::parse($failedAt);
    }
}
