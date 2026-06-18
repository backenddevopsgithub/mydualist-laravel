<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AdminDashboardCacheService extends Service
{
    private const KEY_PREFIX = 'admin.dashboard.';

    public function remember(string $key, callable $callback): mixed
    {
        return Cache::remember(
            self::KEY_PREFIX.$key,
            $this->ttl(),
            $callback,
        );
    }

    public function ttl(): int
    {
        return max(60, (int) config('mydualist.admin_dashboard.cache_ttl_seconds', 600));
    }

    public function forget(string $key): void
    {
        Cache::forget(self::KEY_PREFIX.$key);
    }
}
