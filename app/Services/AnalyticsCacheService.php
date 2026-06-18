<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class AnalyticsCacheService extends Service
{
    public const VERSION_KEY = 'analytics.cache_version';

    public const PENDING_INVALIDATION_KEY = 'analytics.cache_invalidation_pending_at';

    public function version(): int
    {
        $this->processPendingInvalidation();

        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public function invalidate(): void
    {
        if ($this->debounceSeconds() <= 0) {
            $this->bumpVersion();

            return;
        }

        if (Cache::has(self::PENDING_INVALIDATION_KEY)) {
            return;
        }

        Cache::put(
            self::PENDING_INVALIDATION_KEY,
            now()->timestamp,
            $this->pendingKeyTtl(),
        );
    }

    public function processPendingInvalidation(): void
    {
        $pendingAt = Cache::get(self::PENDING_INVALIDATION_KEY);

        if ($pendingAt === null) {
            return;
        }

        if (now()->timestamp - (int) $pendingAt < $this->debounceSeconds()) {
            return;
        }

        Cache::forget(self::PENDING_INVALIDATION_KEY);
        $this->bumpVersion();
    }

    public function bumpVersion(): void
    {
        if (! Cache::has(self::VERSION_KEY)) {
            Cache::forever(self::VERSION_KEY, 1);
        }

        Cache::increment(self::VERSION_KEY);
    }

    public function debounceSeconds(): int
    {
        return max(0, (int) config('mydualist.analytics.cache_invalidation_debounce_seconds', 60));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function key(string $base, array $filters = []): string
    {
        return $base.'.v'.$this->version().'.'.md5(json_encode($filters));
    }

    private function pendingKeyTtl(): int
    {
        return max(120, $this->debounceSeconds() * 2);
    }
}
