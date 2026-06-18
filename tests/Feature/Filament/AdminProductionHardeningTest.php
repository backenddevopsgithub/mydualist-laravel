<?php

use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Jobs\GenerateAdminExportJob;
use App\Models\AdminExport;
use App\Models\MediaLibraryItem;
use App\Models\QueueActionLog;
use App\Models\User;
use App\Policies\MediaLibraryPolicy;
use App\Policies\QueueMonitorPolicy;
use App\Services\AdminExportMonitorService;
use App\Services\AdminExportService;
use App\Services\AnalyticsQueryService;
use App\Support\ExceptionSanitizer;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

test('media library uses private media disk configuration', function () {
    $root = str_replace('\\', '/', (string) config('filesystems.disks.media.root'));

    expect(config('media-library.disk_name'))->toBe('media')
        ->and($root)->toContain('private/media');
});

test('media library conversions are queued by default', function () {
    expect(config('media-library.queue_conversions_by_default'))->toBeTrue();

    $reflection = new ReflectionMethod(MediaLibraryItem::class, 'registerMediaConversions');
    $source = file_get_contents($reflection->getFileName());

    expect($source)->not->toContain('nonQueued()');
});

test('media library policy is registered with the gate', function () {
    $admin = User::factory()->admin()->create();

    expect(Gate::getPolicyFor(MediaLibraryItem::class))->toBeInstanceOf(MediaLibraryPolicy::class)
        ->and($admin->can('viewAny', MediaLibraryItem::class))->toBeTrue();
});

test('queue flush and retry all require super admin', function () {
    $admin = User::factory()->admin()->create();
    $superAdmin = User::factory()->admin()->create(['email' => 'super@example.com']);

    config(['mydualist.super_admin_emails' => ['super@example.com']]);

    $policy = app(QueueMonitorPolicy::class);

    expect($policy->retryFailedJob($admin))->toBeTrue()
        ->and($policy->retryAllFailedJobs($admin))->toBeFalse()
        ->and($policy->flushFailedJobs($admin))->toBeFalse()
        ->and($policy->retryAllFailedJobs($superAdmin))->toBeTrue()
        ->and($policy->flushFailedJobs($superAdmin))->toBeTrue();
});

test('queue actions are written to the audit log', function () {
    $superAdmin = User::factory()->admin()->create(['email' => 'super@example.com']);
    config(['mydualist.super_admin_emails' => ['super@example.com']]);

    $this->actingAs($superAdmin);

    app(\App\Services\QueueActionLogService::class)->record(
        $superAdmin,
        'flush_failed_jobs',
        [],
        '127.0.0.1',
    );

    expect(QueueActionLog::query()->count())->toBe(1)
        ->and(QueueActionLog::query()->first()->action)->toBe('flush_failed_jobs');
});

test('export download urls are temporary signed routes', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.csv', 'name,value');

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UserAnalytics,
        'status' => AdminExportStatus::Completed,
        'filters' => [],
        'file_name' => 'user-analytics.csv',
        'file_path' => 'exports/test.csv',
        'completed_at' => now(),
    ]);

    $url = $export->downloadUrl();

    expect($url)->toContain('signature=');

    $this->actingAs($admin)
        ->get($url)
        ->assertOk();
});

test('exception sanitizer hides internal details outside debug mode', function () {
    config(['app.debug' => false]);

    $message = ExceptionSanitizer::forUser(new RuntimeException('SQLSTATE secret database details'));

    expect($message)->toBe('An unexpected error occurred. Please try again later.');
});

test('failed exports notify the requesting admin', function () {
    config(['app.debug' => false]);

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UserAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'user-analytics.csv',
    ]);

    $analytics = Mockery::mock(AnalyticsQueryService::class);
    $analytics->shouldReceive('userAnalyticsQuery')->andThrow(new RuntimeException('database secret'));
    app()->instance(AnalyticsQueryService::class, $analytics);

    try {
        app(AdminExportService::class)->generate($export);
    } catch (RuntimeException) {
        //
    }

    expect($export->refresh()->status)->toBe(AdminExportStatus::Failed)
        ->and($export->error_message)->toBe('An unexpected error occurred.')
        ->and($admin->notifications()->count())->toBe(1);
});

test('stuck export monitor notifies admins when exports fail', function () {
    $admin = User::factory()->admin()->create();

    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::KeywordAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'keyword-analytics.csv',
    ]);
    $export->forceFill([
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
    ])->save();

    app(AdminExportMonitorService::class)->markStuckExportsAsFailed();

    expect($export->refresh()->status)->toBe(AdminExportStatus::Failed)
        ->and($admin->notifications()->count())->toBe(1);
});

test('export job failure handler records sanitized failures', function () {
    config(['app.debug' => false]);

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UniqueUsers,
        'status' => AdminExportStatus::Processing,
        'filters' => [],
        'file_name' => 'unique-users.csv',
    ]);

    (new GenerateAdminExportJob($export))->failed(new RuntimeException('queue worker secret'));

    expect($export->refresh()->status)->toBe(AdminExportStatus::Failed)
        ->and($export->error_message)->toBe('An unexpected error occurred.');
});

test('analytics views use self hosted chart js asset', function () {
    $categoryView = file_get_contents(resource_path('views/filament/pages/category-analytics.blade.php'));
    $keywordView = file_get_contents(resource_path('views/filament/pages/keyword-analytics.blade.php'));

    expect($categoryView)->toContain("asset('js/filament/widgets/components/chart.js')")
        ->and($keywordView)->toContain("asset('js/filament/widgets/components/chart.js')")
        ->and($categoryView)->not->toContain('cdn.jsdelivr.net')
        ->and($keywordView)->not->toContain('cdn.jsdelivr.net');
});
