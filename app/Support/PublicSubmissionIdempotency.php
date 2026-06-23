<?php

namespace App\Support;

use App\Models\DuaList;
use Illuminate\Support\Facades\Cache;

class PublicSubmissionIdempotency
{
    private const CACHE_PREFIX = 'public-submission-batch:';

    public static function remember(string $batchKey, DuaList $duaList, int $count): void
    {
        Cache::put(self::cacheKey($batchKey, $duaList), $count, now()->addDay());
    }

    public static function findExistingCount(string $batchKey, DuaList $duaList): ?int
    {
        $cached = Cache::get(self::cacheKey($batchKey, $duaList));

        return is_int($cached) ? $cached : null;
    }

    private static function cacheKey(string $batchKey, DuaList $duaList): string
    {
        return self::CACHE_PREFIX.$duaList->id.':'.$batchKey;
    }
}
