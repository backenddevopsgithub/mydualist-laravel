<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AnalyticsCacheService extends Service
{
    public const VERSION_KEY = 'analytics.cache_version';

    public function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function invalidate(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);
        }

        Cache::increment(self::VERSION_KEY);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function key(string $base, array $filters = []): string
    {
        return $base.'.v'.$this->version().'.'.md5(json_encode($filters));
    }
}
