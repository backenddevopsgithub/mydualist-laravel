<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\AnalyticsCacheService;
use App\Services\KeywordAnalyticsService;
use Illuminate\Support\Facades\Cache;

test('analytics cache debounces version bumps during write bursts', function () {
    Cache::flush();
    config(['mydualist.analytics.cache_invalidation_debounce_seconds' => 60]);

    $cache = app(AnalyticsCacheService::class);
    $versionBefore = $cache->version();

    for ($i = 0; $i < 100; $i++) {
        $cache->invalidate();
    }

    expect($cache->version())->toBe($versionBefore)
        ->and(Cache::has(AnalyticsCacheService::PENDING_INVALIDATION_KEY))->toBeTrue();

    $this->travel(61)->seconds();

    expect($cache->version())->toBe($versionBefore + 1)
        ->and(Cache::has(AnalyticsCacheService::PENDING_INVALIDATION_KEY))->toBeFalse();
});

test('submission writes schedule a single debounced analytics invalidation', function () {
    Cache::flush();
    config(['mydualist.analytics.cache_invalidation_debounce_seconds' => 60]);

    $cache = app(AnalyticsCacheService::class);
    $versionBefore = $cache->version();
    $list = DuaList::factory()->create();

    DuaSubmission::factory()->count(100)->create(['dua_list_id' => $list->id]);

    expect($cache->version())->toBe($versionBefore)
        ->and(Cache::has(AnalyticsCacheService::PENDING_INVALIDATION_KEY))->toBeTrue();
});

test('analytics cache invalidates immediately when debounce is disabled', function () {
    Cache::flush();
    config(['mydualist.analytics.cache_invalidation_debounce_seconds' => 0]);

    $cache = app(AnalyticsCacheService::class);
    $versionBefore = $cache->version();

    $cache->invalidate();
    $cache->invalidate();

    expect($cache->version())->toBe($versionBefore + 2)
        ->and(Cache::has(AnalyticsCacheService::PENDING_INVALIDATION_KEY))->toBeFalse();
});

test('keyword analytics computing lock keys still resolve while invalidation is pending', function () {
    Cache::flush();
    config(['mydualist.analytics.cache_invalidation_debounce_seconds' => 60]);

    $cache = app(AnalyticsCacheService::class);
    $keywords = app(KeywordAnalyticsService::class);
    $filters = ['date_from' => '2026-01-01'];

    $cacheKey = $keywords->cacheKey($filters);
    $lockKey = $keywords->computingLockKey($filters);

    $cache->invalidate();

    expect($keywords->cacheKey($filters))->toBe($cacheKey)
        ->and($keywords->computingLockKey($filters))->toBe($lockKey)
        ->and(Cache::add($lockKey, true, 60))->toBeTrue();
});

test('analytics cache version bumps after debounce when data changes via observer', function () {
    Cache::flush();
    config(['mydualist.analytics.cache_invalidation_debounce_seconds' => 30]);

    $cache = app(AnalyticsCacheService::class);
    $versionBefore = $cache->version();

    DuaList::factory()->create();

    expect($cache->version())->toBe($versionBefore);

    $this->travel(31)->seconds();

    expect($cache->version())->toBe($versionBefore + 1);
});
