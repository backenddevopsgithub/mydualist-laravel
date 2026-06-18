<?php

use App\Domains\Submissions\Actions\DismissReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\HideReportedDuaSubmissionAction;
use App\Domains\Submissions\Actions\ReportDuaSubmissionAction;
use App\Enums\DuaSubmissionModerationAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Resources\ReportedDuaSubmissionResource;
use App\Filament\Resources\ReportedDuaSubmissionResource\Pages\ListReportedDuaSubmissions;
use App\Filament\Resources\ReportedDuaSubmissionResource\Pages\ViewReportedDuaSubmission;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\DuaSubmissionModerationLog;
use App\Models\User;
use Livewire\Livewire;

test('reported duas queue is restricted to active admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->get('/admin/reported-duas')->assertRedirect('/admin/login');

    $this->actingAs($user)
        ->get('/admin/reported-duas')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/reported-duas')
        ->assertOk();
});

test('reported duas queue only shows submissions with active reports', function () {
    $admin = User::factory()->admin()->create();
    $list = DuaList::factory()->create();

    $reported = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'content' => 'Please make dua for my family',
    ]);

    $pending = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Unreported dua content',
    ]);

    $dismissed = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Pending,
        'reported_at' => null,
        'report_reason' => null,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->assertCanSeeTableRecords([$reported])
        ->assertCanNotSeeTableRecords([$pending, $dismissed]);
});

test('reported duas queue supports report reason and status filters', function () {
    $admin = User::factory()->admin()->create();
    $list = DuaList::factory()->create();

    $spamReport = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'report_reason' => 'spam',
        'status' => DuaSubmissionStatus::Reported,
    ]);

    $hiddenReport = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'report_reason' => 'offensive',
        'status' => DuaSubmissionStatus::Hidden,
        'hidden_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->filterTable('report_reason', 'spam')
        ->assertCanSeeTableRecords([$spamReport])
        ->assertCanNotSeeTableRecords([$hiddenReport])
        ->resetTableFilters()
        ->filterTable('status', DuaSubmissionStatus::Hidden->value)
        ->assertCanSeeTableRecords([$hiddenReport])
        ->assertCanNotSeeTableRecords([$spamReport]);
});

test('reported duas queue supports occasion and moderated filters', function () {
    $admin = User::factory()->admin()->create();
    $hajjList = DuaList::factory()->create(['occasion' => 'hajj']);
    $ramadanList = DuaList::factory()->create(['occasion' => 'ramadan']);

    $unmoderated = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $hajjList->id,
        'moderated_at' => null,
    ]);

    $moderated = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $ramadanList->id,
        'moderated_at' => now(),
        'moderated_by' => $admin->id,
        'moderation_action' => DuaSubmissionModerationAction::Hide->value,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->filterTable('occasion', 'ramadan')
        ->assertCanSeeTableRecords([$moderated])
        ->assertCanNotSeeTableRecords([$unmoderated])
        ->resetTableFilters()
        ->filterTable('moderated', true)
        ->assertCanSeeTableRecords([$moderated])
        ->assertCanNotSeeTableRecords([$unmoderated]);
});

test('reported duas queue supports search by submitter and content', function () {
    $admin = User::factory()->admin()->create();
    $list = DuaList::factory()->create(['title' => 'Family Duas']);

    $matching = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'content' => 'Please remember my parents',
    ]);

    $other = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'first_name' => 'Yusuf',
        'last_name' => 'Ali',
        'email' => 'yusuf@example.com',
        'content' => 'Different dua request',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->searchTable('amina@example.com')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->searchTable('Please remember my parents')
        ->assertCanSeeTableRecords([$matching])
        ->assertCanNotSeeTableRecords([$other]);
});

test('reported duas row actions hide restore and dismiss submissions', function () {
    $admin = User::factory()->admin()->create();
    $list = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Reported,
        'status_before_report' => DuaSubmissionStatus::Completed->value,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableAction('hide', $submission, data: [
            'moderation_notes' => 'Hidden after review',
        ]);

    $submission->refresh();

    expect($submission->status)->toBe(DuaSubmissionStatus::Hidden)
        ->and($submission->moderated_by)->toBe($admin->id)
        ->and($submission->moderation_action)->toBe(DuaSubmissionModerationAction::Hide->value)
        ->and($submission->moderation_notes)->toBe('Hidden after review')
        ->and($submission->reported_at)->not->toBeNull();

    $this->assertDatabaseHas('dua_submission_moderation_logs', [
        'dua_submission_id' => $submission->id,
        'moderator_id' => $admin->id,
        'action' => DuaSubmissionModerationAction::Hide->value,
        'new_status' => DuaSubmissionStatus::Hidden->value,
        'notes' => 'Hidden after review',
    ]);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableAction('restore', $submission, data: [
            'moderation_notes' => 'Restored for list owner',
        ]);

    $submission->refresh();

    expect($submission->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($submission->moderation_action)->toBe(DuaSubmissionModerationAction::Restore->value)
        ->and($submission->reported_at)->toBeNull();

    $submission->forceFill([
        'status' => DuaSubmissionStatus::Reported,
        'reported_at' => now(),
        'report_reason' => 'spam',
        'report_count' => 1,
        'status_before_report' => DuaSubmissionStatus::Completed->value,
    ])->save();

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableAction('dismiss', $submission, data: [
            'moderation_notes' => 'False report',
        ]);

    $submission->refresh();

    expect($submission->status)->toBe(DuaSubmissionStatus::Completed)
        ->and($submission->reported_at)->toBeNull()
        ->and($submission->report_reason)->toBeNull()
        ->and($submission->report_count)->toBe(0)
        ->and($submission->moderation_action)->toBe(DuaSubmissionModerationAction::Dismiss->value);
});

test('reported duas bulk actions apply moderation to selected records', function () {
    $admin = User::factory()->admin()->create();
    $list = DuaList::factory()->create();

    $first = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Reported,
    ]);

    $second = DuaSubmission::factory()->reported()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Reported,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableBulkAction('hideSelected', [$first, $second]);

    expect($first->refresh()->status)->toBe(DuaSubmissionStatus::Hidden)
        ->and($second->refresh()->status)->toBe(DuaSubmissionStatus::Hidden);

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableBulkAction('restoreSelected', [$first, $second]);

    expect($first->refresh()->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($second->refresh()->status)->toBe(DuaSubmissionStatus::Pending);

    $first->forceFill([
        'status' => DuaSubmissionStatus::Reported,
        'reported_at' => now(),
        'report_reason' => 'spam',
        'report_count' => 1,
        'status_before_report' => DuaSubmissionStatus::Pending->value,
    ])->save();

    $second->forceFill([
        'status' => DuaSubmissionStatus::Reported,
        'reported_at' => now(),
        'report_reason' => 'duplicate',
        'report_count' => 1,
        'status_before_report' => DuaSubmissionStatus::Pending->value,
    ])->save();

    Livewire::test(ListReportedDuaSubmissions::class)
        ->callTableBulkAction('dismissSelected', [$first, $second]);

    expect($first->refresh()->reported_at)->toBeNull()
        ->and($second->refresh()->reported_at)->toBeNull();
});

test('reported dua detail view shows moderation history', function () {
    $admin = User::factory()->admin()->create();
    $submission = DuaSubmission::factory()->reported()->create();

    DuaSubmissionModerationLog::query()->create([
        'dua_submission_id' => $submission->id,
        'moderator_id' => $admin->id,
        'action' => DuaSubmissionModerationAction::Hide,
        'previous_status' => DuaSubmissionStatus::Reported->value,
        'new_status' => DuaSubmissionStatus::Hidden->value,
        'notes' => 'Initial review',
        'created_at' => now(),
    ]);

    $this->actingAs($admin);

    Livewire::test(ViewReportedDuaSubmission::class, [
        'record' => $submission->getRouteKey(),
    ])
        ->assertOk()
        ->assertSee('Initial review')
        ->assertSee($submission->content);
});

test('auto hide threshold only applies to new reports and is disabled by default', function () {
    config()->set('mydualist.moderation.auto_hide_threshold', null);

    $list = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Reported,
        'report_count' => 5,
        'reported_at' => now()->subDay(),
        'report_reason' => 'spam',
    ]);

    app(ReportDuaSubmissionAction::class)($submission, [
        'report_reason' => 'spam',
    ]);

    expect($submission->fresh()->status)->toBe(DuaSubmissionStatus::Reported)
        ->and($submission->fresh()->report_count)->toBe(6);

    config()->set('mydualist.moderation.auto_hide_threshold', 2);

    $freshSubmission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    app(ReportDuaSubmissionAction::class)($freshSubmission, [
        'report_reason' => 'spam',
    ]);

    expect($freshSubmission->fresh()->status)->toBe(DuaSubmissionStatus::Reported)
        ->and($freshSubmission->fresh()->report_count)->toBe(1);

    app(ReportDuaSubmissionAction::class)($freshSubmission->fresh(), [
        'report_reason' => 'offensive',
    ]);

    $autoHidden = $freshSubmission->fresh();

    expect($autoHidden->status)->toBe(DuaSubmissionStatus::Hidden)
        ->and($autoHidden->report_count)->toBe(2)
        ->and($autoHidden->moderation_action)->toBe(DuaSubmissionModerationAction::AutoHide->value);

    $this->assertDatabaseHas('dua_submission_moderation_logs', [
        'dua_submission_id' => $autoHidden->id,
        'action' => DuaSubmissionModerationAction::AutoHide->value,
        'moderator_id' => null,
    ]);
});

test('moderation domain actions enforce admin only authorization through policy', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $submission = DuaSubmission::factory()->reported()->create();

    expect($admin->can('moderate', $submission))->toBeTrue()
        ->and($admin->can('moderateAny', DuaSubmission::class))->toBeTrue()
        ->and($user->can('moderate', $submission))->toBeFalse()
        ->and($user->can('moderateAny', DuaSubmission::class))->toBeFalse();

    app(HideReportedDuaSubmissionAction::class)($submission, $admin, 'admin action');

    expect($submission->fresh()->status)->toBe(DuaSubmissionStatus::Hidden);

    expect(ReportedDuaSubmissionResource::canViewAny())->toBeFalse();

    $this->actingAs($admin);
    expect(ReportedDuaSubmissionResource::canViewAny())->toBeTrue();

    $this->actingAs($user);
    expect(ReportedDuaSubmissionResource::canViewAny())->toBeFalse();
});

test('report action stores status before report on first report', function () {
    $list = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'status' => DuaSubmissionStatus::Completed,
        'completed_at' => now(),
    ]);

    app(ReportDuaSubmissionAction::class)($submission, [
        'report_reason' => 'irrelevant',
    ]);

    $submission->refresh();

    expect($submission->status)->toBe(DuaSubmissionStatus::Reported)
        ->and($submission->status_before_report)->toBe(DuaSubmissionStatus::Completed->value)
        ->and($submission->report_count)->toBe(1);

    app(ReportDuaSubmissionAction::class)($submission, [
        'report_reason' => 'spam',
    ]);

    expect($submission->fresh()->report_count)->toBe(2);
});

test('dismiss report action restores previous status and clears report fields', function () {
    $admin = User::factory()->admin()->create();
    $submission = DuaSubmission::factory()->reported()->create([
        'status' => DuaSubmissionStatus::Reported,
        'status_before_report' => DuaSubmissionStatus::Completed->value,
        'report_count' => 2,
    ]);

    app(DismissReportedDuaSubmissionAction::class)($submission, $admin, 'Cleared after review');

    $submission->refresh();

    expect($submission->status)->toBe(DuaSubmissionStatus::Completed)
        ->and($submission->reported_at)->toBeNull()
        ->and($submission->report_reason)->toBeNull()
        ->and($submission->report_count)->toBe(0)
        ->and($submission->status_before_report)->toBeNull()
        ->and($submission->moderation_notes)->toBe('Cleared after review');
});
