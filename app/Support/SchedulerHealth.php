<?php

namespace App\Support;

use Carbon\CarbonInterface;

class SchedulerHealth
{
    public const CACHE_KEY = 'scheduler:last_run';

    public const STATUS_HEALTHY = 'healthy';

    public const STATUS_WARNING = 'warning';

    public const STATUS_OFFLINE = 'offline';

    public static function status(?CarbonInterface $lastRun): string
    {
        if ($lastRun === null) {
            return self::STATUS_OFFLINE;
        }

        $minutes = $lastRun->diffInMinutes(now());

        if ($minutes <= 2) {
            return self::STATUS_HEALTHY;
        }

        if ($minutes <= 5) {
            return self::STATUS_WARNING;
        }

        return self::STATUS_OFFLINE;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_HEALTHY => 'Healthy',
            self::STATUS_WARNING => 'Warning',
            default => 'Offline',
        };
    }
}
