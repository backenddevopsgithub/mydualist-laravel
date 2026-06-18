<?php

use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Exceptions\AdminExportDuplicateException;
use App\Exceptions\AdminExportRateLimitException;
use App\Models\AdminExport;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\AdminExportCleanupService;
use App\Services\AdminExportService;
use App\Services\AnalyticsCacheService;
use App\Services\AnalyticsQueryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

test('expired admin exports and files are pruned by cleanup service', function () {
    Storage::fake('local');
    config(['mydualist.admin_exports.retention_days' => 7]);

    $admin = User::factory()->admin()->create();
    $path = 'exports/expired.csv';
    Storage::disk('local')->put($path, 'csv');

    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UserAnalytics,
        'status' => AdminExportStatus::Completed,
        'filters' => [],
        'file_name' => 'user-analytics.csv',
        'file_path' => $path,
        'completed_at' => now()->subDays(8),
    ]);

    $pruned = app(AdminExportCleanupService::class)->pruneExpiredExports();

    expect($pruned)->toBe(1)
        ->and(AdminExport::query()->find($export->id))->toBeNull()
        ->and(Storage::disk('local')->exists($path))->toBeFalse();
});

test('admin export generation is idempotent when export is not pending', function () {
    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UniqueUsers,
        'status' => AdminExportStatus::Processing,
        'filters' => [],
        'file_name' => 'unique-users.csv',
        'file_path' => 'exports/existing.csv',
        'row_count' => 5,
        'completed_at' => now(),
    ]);

    app(AdminExportService::class)->generate($export->fresh());

    expect($export->refresh()->status)->toBe(AdminExportStatus::Processing)
        ->and($export->file_path)->toBe('exports/existing.csv')
        ->and($export->row_count)->toBe(5);
});

test('admin export generation removes partial files on failure', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UserAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'user-analytics.csv',
    ]);

    $analytics = Mockery::mock(AnalyticsQueryService::class);
    $analytics->shouldReceive('userAnalyticsQuery')->andThrow(new RuntimeException('export failed'));
    app()->instance(AnalyticsQueryService::class, $analytics);

    try {
        app(AdminExportService::class)->generate($export);
    } catch (RuntimeException) {
        //
    }

    $export->refresh();

    expect($export->status)->toBe(AdminExportStatus::Failed)
        ->and(collect(Storage::disk('local')->allFiles('exports')))->toBeEmpty();
});

test('duplicate pending admin exports are blocked for the same user', function () {
    $admin = User::factory()->admin()->create();

    AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::DuaListAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'dua-list-analytics.csv',
    ]);

    expect(fn () => app(AdminExportService::class)->queue($admin, AdminExportType::UserAnalytics))
        ->toThrow(AdminExportDuplicateException::class);
});

test('admin export creation is rate limited per user', function () {
    Queue::fake();
    config(['mydualist.admin_exports.rate_limit_per_hour' => 2]);

    $admin = User::factory()->admin()->create();
    $service = app(AdminExportService::class);
    RateLimiter::clear('admin-exports:'.$admin->id);

    foreach ([AdminExportType::DuaListAnalytics, AdminExportType::UserAnalytics] as $type) {
        $export = $service->queue($admin, $type);
        $export->update([
            'status' => AdminExportStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    expect(fn () => $service->queue($admin, AdminExportType::SubmissionAnalytics))
        ->toThrow(AdminExportRateLimitException::class);
});

test('user analytics query does not eager load dua lists', function () {
    $eagerLoads = app(AnalyticsQueryService::class)->userAnalyticsQuery([])->getEagerLoads();

    expect($eagerLoads)->not->toHaveKey('duaLists');
});

test('submission admin test scopes use cached admin emails instead of user subqueries', function () {
    User::factory()->admin()->create(['email' => 'admin-scalability@example.com']);
    Cache::flush();
    app(AnalyticsQueryService::class)->adminEmails();

    DB::flushQueryLog();
    DB::enableQueryLog();

    DuaSubmission::query()->whereNotAdminTest()->count();

    $userQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `users`'));

    expect($userQueries)->toBeEmpty();
});

test('category analytics rows compute percentages from filtered totals', function () {
    DuaList::factory()->count(3)->create(['occasion' => 'wedding']);
    DuaList::factory()->create(['occasion' => 'funeral']);

    $rows = app(AnalyticsQueryService::class)->categoryAnalyticsRows([]);
    $wedding = $rows->firstWhere('occasion', 'wedding');

    expect($wedding?->percentage)->toBe(75.0);
});

test('analytics cache invalidates when underlying data changes', function () {
    Cache::flush();

    $cache = app(AnalyticsCacheService::class);
    $filters = [];
    $keyBefore = $cache->key('analytics.user_metrics', $filters);

    app(AnalyticsQueryService::class)->userMetrics($filters);

    expect(Cache::has($keyBefore))->toBeTrue();

    DuaList::factory()->create();

    $keyAfter = $cache->key('analytics.user_metrics', $filters);

    expect($keyAfter)->not->toBe($keyBefore)
        ->and(Cache::has($keyAfter))->toBeFalse();
});

test('cleanup admin exports command reports pruned count', function () {
    Storage::fake('local');
    config(['mydualist.admin_exports.retention_days' => 1]);

    $admin = User::factory()->admin()->create();

    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::KeywordAnalytics,
        'status' => AdminExportStatus::Failed,
        'filters' => [],
        'file_name' => 'keyword-analytics.csv',
    ]);
    $export->forceFill([
        'updated_at' => now()->subDays(2),
        'created_at' => now()->subDays(2),
    ])->save();

    $this->artisan('admin:cleanup-exports')
        ->expectsOutputToContain('Removed 1 expired admin export(s).')
        ->assertSuccessful();
});
