<?php

use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Jobs\GenerateAdminExportJob;
use App\Models\AdminExport;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('analytics and system pages are restricted to active admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $routes = [
        '/admin/dua-list-analytics',
        '/admin/user-analytics',
        '/admin/unique-users',
        '/admin/category-analytics',
        '/admin/submission-analytics',
        '/admin/keyword-analytics',
        '/admin/queue-monitor',
        '/admin/email-health',
        '/admin/feature-flags',
        '/admin/migration-status',
        '/admin/media-libraries',
    ];

    foreach ($routes as $route) {
        $this->actingAs($user)->get($route)->assertForbidden();
        $this->actingAs($admin)->get($route)->assertOk();
    }
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

test('analytics query service aggregates list and submission metrics', function () {
    $user = User::factory()->create();
    DuaList::factory()->count(2)->create(['user_id' => $user->id]);

    $list = DuaList::query()->first();
    DuaSubmission::factory()->create(['dua_list_id' => $list->id, 'status' => 'completed']);
    DuaSubmission::factory()->create(['dua_list_id' => $list->id, 'status' => 'pending']);

    $metrics = app(\App\Services\AnalyticsQueryService::class)->duaListMetrics();

    expect($metrics['total_lists'])->toBeGreaterThanOrEqual(2)
        ->and($metrics['total_submissions'])->toBeGreaterThanOrEqual(2);
});

test('admins cannot delete themselves from user resource policy', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('delete', $admin))->toBeFalse();
});

test('admins can create users from admin panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/users/create')
        ->assertOk();
});
