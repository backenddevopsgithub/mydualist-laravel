<?php

use App\Domains\Notifications\Notifications\SubmissionExportReadyNotification;
use App\Enums\AdminExportStatus;
use App\Enums\AdminExportType;
use App\Jobs\GenerateAdminExportJob;
use App\Models\AdminExport;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\AdminExportService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

test('profile submission export is queued instead of streaming synchronously', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id, 'title' => 'Family List']);
    DuaSubmission::factory()->count(2)->create(['dua_list_id' => $list->id]);

    $this->actingAs($user)
        ->post(route('dashboard.profile.submissions.export'), [
            'dua_list_id' => $list->id,
        ])
        ->assertRedirect(route('dashboard.profile'))
        ->assertSessionHas('status');

    $export = AdminExport::query()->first();

    expect($export)->not->toBeNull()
        ->and($export->type)->toBe(AdminExportType::UserListSubmissions)
        ->and($export->status)->toBe(AdminExportStatus::Pending)
        ->and($export->filters)->toMatchArray([
            'dua_list_id' => $list->id,
            'dua_list_title' => 'Family List',
        ]);

    Queue::assertPushed(GenerateAdminExportJob::class);
});

test('queued user submission export generates a downloadable csv file', function () {
    Storage::fake('local');
    Notification::fake();

    $user = User::factory()->create(['email' => 'owner@example.com']);
    $list = DuaList::factory()->create(['user_id' => $user->id, 'title' => 'Export List']);
    DuaSubmission::factory()->count(3)->create(['dua_list_id' => $list->id]);

    $export = app(AdminExportService::class)->queueUserListSubmissions($user, $list->id);
    app(AdminExportService::class)->generate($export->fresh());

    $export->refresh();

    expect($export->status)->toBe(AdminExportStatus::Completed)
        ->and($export->row_count)->toBe(3)
        ->and(Storage::disk('local')->exists((string) $export->file_path))->toBeTrue();

    Notification::assertSentTo($user, SubmissionExportReadyNotification::class);

    $downloadUrl = URL::temporarySignedRoute(
        'dashboard.exports.download',
        now()->addHour(),
        ['export' => $export->id],
    );

    $this->actingAs($user)
        ->get($downloadUrl)
        ->assertOk()
        ->assertDownload($export->file_name);
});

test('user submission export creation is rate limited per user', function () {
    Queue::fake();
    config(['mydualist.user_exports.rate_limit_per_hour' => 1]);

    $user = User::factory()->create();
    $lists = DuaList::factory()->count(2)->create(['user_id' => $user->id]);
    RateLimiter::clear('user-exports:'.$user->id);

    app(AdminExportService::class)->queueUserListSubmissions($user, $lists[0]->id)
        ->update([
            'status' => AdminExportStatus::Completed,
            'completed_at' => now(),
        ]);

    expect(fn () => app(AdminExportService::class)->queueUserListSubmissions($user, $lists[1]->id))
        ->toThrow(\App\Exceptions\AdminExportRateLimitException::class);
});

test('duplicate pending user submission exports are blocked', function () {
    Queue::fake();

    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    app(AdminExportService::class)->queueUserListSubmissions($user, $list->id);

    expect(fn () => app(AdminExportService::class)->queueUserListSubmissions($user, $list->id))
        ->toThrow(\App\Exceptions\AdminExportDuplicateException::class);
});

test('api profile submission export returns queued response', function () {
    Queue::fake();

    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->getJson('/api/v1/profile/submissions/export?dua_list_id='.$list->id)
        ->assertAccepted()
        ->assertJsonPath('data.export_id', AdminExport::query()->value('id'))
        ->assertJsonPath('data.status', AdminExportStatus::Pending->value);

    Queue::assertPushed(GenerateAdminExportJob::class);
});
