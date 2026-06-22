<?php

use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Exceptions\AdminExportQueueException;
use App\Jobs\ComputeKeywordAnalyticsJob;
use App\Jobs\GenerateAdminExportJob;
use App\Jobs\RunMigrationValidationJob;
use App\Models\AdminExport;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\AdminExportMonitorService;
use App\Services\AdminExportService;
use App\Services\KeywordAnalyticsService;
use App\Services\LegacyImport\Validation\MigrationValidationService;
use App\Services\MigrationStatusService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

test('export download route is registered behind authenticated filament routes', function () {
    $route = collect(Route::getRoutes())->first(
        fn ($registeredRoute) => $registeredRoute->getName() === 'filament.admin.exports.download',
    );

    expect($route)->not->toBeNull();

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain(\Filament\Http\Middleware\Authenticate::class)
        ->and($middleware)->toContain('signed');
});

test('guests are redirected when accessing export downloads', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.csv', 'name,value');

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::DuaListAnalytics,
        'status' => AdminExportStatus::Completed,
        'filters' => [],
        'file_name' => 'dua-list-analytics.csv',
        'file_path' => 'exports/test.csv',
        'completed_at' => now(),
    ]);

    $this->get($export->downloadUrl())
        ->assertRedirect('/admin/login');
});

test('unsigned export download requests are rejected', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.csv', 'name,value');

    $admin = User::factory()->admin()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::DuaListAnalytics,
        'status' => AdminExportStatus::Completed,
        'filters' => [],
        'file_name' => 'dua-list-analytics.csv',
        'file_path' => 'exports/test.csv',
        'completed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.exports.download', ['export' => $export->id]))
        ->assertForbidden();
});

test('suspended admins cannot download their own exports', function () {
    Storage::fake('local');
    Storage::disk('local')->put('exports/test.csv', 'name,value');

    $admin = User::factory()->admin()->suspended()->create();
    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::DuaListAnalytics,
        'status' => AdminExportStatus::Completed,
        'filters' => [],
        'file_name' => 'dua-list-analytics.csv',
        'file_path' => 'exports/test.csv',
        'completed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get($export->downloadUrl())
        ->assertForbidden();
});

test('admin exports cannot be queued with sync connection in production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['queue.default' => 'sync']);

    $admin = User::factory()->admin()->create();

    expect(fn () => app(AdminExportService::class)->queue($admin, AdminExportType::DuaListAnalytics))
        ->toThrow(AdminExportQueueException::class);
});

test('admin export monitor marks stuck pending and processing exports as failed', function () {
    $admin = User::factory()->admin()->create();

    $pending = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::UserAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'user-analytics.csv',
    ]);
    $pending->forceFill([
        'created_at' => now()->subMinutes(20),
        'updated_at' => now()->subMinutes(20),
    ])->save();

    $processing = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::SubmissionAnalytics,
        'status' => AdminExportStatus::Processing,
        'filters' => [],
        'file_name' => 'submission-analytics.csv',
    ]);
    $processing->forceFill([
        'created_at' => now()->subMinutes(40),
        'updated_at' => now()->subMinutes(40),
    ])->save();

    app(AdminExportMonitorService::class)->markStuckExportsAsFailed();

    expect($pending->refresh()->status)->toBe(AdminExportStatus::Failed)
        ->and($processing->refresh()->status)->toBe(AdminExportStatus::Failed);
});

test('migration status service does not run validation when report file is missing', function () {
    $validation = Mockery::mock(MigrationValidationService::class);
    $validation->shouldNotReceive('validate');
    app()->instance(MigrationValidationService::class, $validation);

    $status = app(MigrationStatusService::class)->status();

    expect($status['report_exists'])->toBeFalse()
        ->and($status['totals'])->toBe([])
        ->and($status['live_totals'])->toBeArray()
        ->and($status['failures'])->toBe([]);
});

test('migration status service reads cached validation report without running validation', function () {
    $reportPath = storage_path('app/testing-migration-status-report.json');
    File::ensureDirectoryExists(dirname($reportPath));
    File::put($reportPath, json_encode([
        'generated_at' => '2026-06-19T12:00:00+00:00',
        'validation' => [
            'passed' => true,
            'totals' => ['users' => 10],
            'failures' => [],
            'warnings' => [],
            'mismatches' => [],
        ],
    ]));

    config(['mydualist.legacy.import.validate_report_path' => $reportPath]);

    $validation = Mockery::mock(MigrationValidationService::class);
    $validation->shouldNotReceive('validate');
    app()->instance(MigrationValidationService::class, $validation);

    $status = app(MigrationStatusService::class)->status();

    expect($status['report_exists'])->toBeTrue()
        ->and($status['passed'])->toBeTrue()
        ->and($status['totals']['users'])->toBe(10)
        ->and($status['generated_at'])->toBe('2026-06-19T12:00:00+00:00')
        ->and($status['mismatches'])->toBe([])
        ->and($status['import_sequence'])->not->toBeEmpty();

    File::delete($reportPath);
});

test('migration status service hints when suggestions were not imported', function () {
    User::factory()->create();

    $status = app(MigrationStatusService::class)->status();

    expect(collect($status['warnings'])
        ->contains(fn (array $warning): bool => ($warning['type'] ?? null) === 'suggestions_not_imported'))->toBeTrue();
});

test('keyword analytics reads precomputed cache without dispatching jobs', function () {
    Queue::fake();
    Cache::flush();

    $filters = [];
    $service = app(KeywordAnalyticsService::class);

    $service->storePrecomputed($filters, collect([
        (object) ['keyword' => 'mercy', 'occurrences' => 5],
    ]));

    $keywords = app(\App\Services\AnalyticsQueryService::class)->keywordOccurrences($filters);

    expect($keywords)->toHaveCount(1)
        ->and($keywords->first()->keyword)->toBe('mercy');

    Queue::assertNothingPushed();
});

test('keyword analytics dispatches background computation when cache is missing', function () {
    Queue::fake();
    Cache::flush();

    $keywords = app(\App\Services\AnalyticsQueryService::class)->keywordOccurrences([]);

    expect($keywords)->toBeEmpty();
    Queue::assertPushed(ComputeKeywordAnalyticsJob::class);
});

test('compute keyword analytics job stores precomputed keyword data', function () {
    $list = DuaList::factory()->create();
    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'mercy forgiveness mercy',
    ]);

    $filters = [];
    $service = app(KeywordAnalyticsService::class);
    Cache::put($service->computingLockKey($filters), true, 600);

    (new ComputeKeywordAnalyticsJob($filters))->handle($service);

    $cached = Cache::get($service->cacheKey($filters));

    expect($cached)->toBeArray()
        ->and(collect($cached)->firstWhere('keyword', 'mercy')['occurrences'] ?? null)->toBe(2);
});

test('run migration validation job executes migrate validate command', function () {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate:validate');

    (new RunMigrationValidationJob)->handle();
});

test('admin export job can be queued for analytics csv', function () {
    Queue::fake();

    $admin = User::factory()->admin()->create();

    $export = AdminExport::query()->create([
        'user_id' => $admin->id,
        'type' => AdminExportType::DuaListAnalytics,
        'status' => AdminExportStatus::Pending,
        'filters' => [],
        'file_name' => 'dua-list-analytics.csv',
    ]);

    GenerateAdminExportJob::dispatch($export);

    Queue::assertPushed(GenerateAdminExportJob::class);
});
