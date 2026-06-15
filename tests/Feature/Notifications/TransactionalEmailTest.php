<?php

use App\Domains\Auth\Actions\VerifyEmailAction;
use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Domains\Notifications\Notifications\DuaCompletedNotification;
use App\Domains\Notifications\Notifications\ListCreatedNotification;
use App\Domains\Notifications\Notifications\NewSubmissionNotification;
use App\Domains\Notifications\Notifications\SubmissionQuotaWarningNotification;
use App\Domains\Notifications\Notifications\WelcomeUserNotification;
use App\Domains\Notifications\Services\TransactionalEmailService;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Filament\Widgets\EmailHealthWidget;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EmailSendLog;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('email verification dispatches welcome notification once', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    app(VerifyEmailAction::class)($user);

    Notification::assertSentTo($user, WelcomeUserNotification::class);
    expect($user->fresh()->welcome_email_sent_at)->not->toBeNull();
});

test('welcome notification is not duplicated once sent', function () {
    Notification::fake();

    $user = User::factory()->create(['welcome_email_sent_at' => now()]);

    app(TransactionalEmailService::class)->sendWelcomeIfNeeded($user);

    Notification::assertNothingSent();
});

test('verified list creation sends list created notification immediately', function () {
    Notification::fake();

    $user = User::factory()->create(['first_name' => 'Amina']);

    app(CreateDuaListAction::class)($user, [
        'title' => 'Hajj 2027',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    Notification::assertSentTo($user, ListCreatedNotification::class);
    expect(DuaList::query()->first()->list_created_email_sent_at)->not->toBeNull();
});

test('unverified list creation defers list created email until verification', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create(['first_name' => 'Yusuf']);

    app(CreateDuaListAction::class)($user, [
        'title' => 'Umrah 2027',
        'occasion' => 'umrah',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ]);

    Notification::assertNothingSent();
    expect(DuaList::query()->first()->list_created_email_sent_at)->toBeNull();

    app(VerifyEmailAction::class)($user->fresh());

    Notification::assertSentTo($user, WelcomeUserNotification::class);
    Notification::assertSentTo($user, ListCreatedNotification::class);
});

test('new public submission notifies list owner when email frequency is every submission', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'every_submission',
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'content' => 'Please make dua for my parents.',
    ]);

    Notification::assertSentTo($owner, NewSubmissionNotification::class);
});

test('new public submission does not notify owner when email frequency is daily summary', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Sara',
        'email' => 'sara@example.com',
        'content' => 'Please make dua for my parents.',
    ]);

    Notification::assertNothingSent();
});

test('marking a visible submission complete notifies the submitter', function () {
    Notification::fake();

    $owner = User::factory()->create(['first_name' => 'Arsalan']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'email' => 'submitter@example.com',
        'first_name' => 'Hassan',
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    Notification::assertSentOnDemand(DuaCompletedNotification::class, function ($notification, $channels, $notifiable) {
        return $notifiable->routes['mail'] === 'submitter@example.com';
    });

    expect($submission->fresh()->completion_notified_at)->not->toBeNull();
});

test('completion notification is not resent for the same submission', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'email' => 'submitter@example.com',
        'completion_notified_at' => now(),
        'status' => DuaSubmissionStatus::Completed,
        'completed_at' => now(),
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    Notification::assertNothingSent();
});

test('quota warning notification is sent once when visible slots drop to five', function () {
    Notification::fake();

    $owner = User::factory()->create(['first_name' => 'Free']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(19)->create([
        'dua_list_id' => $duaList->id,
        'is_personal_dua' => false,
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Guest',
        'email' => 'guest@example.com',
        'content' => 'Please remember me.',
    ]);

    Notification::assertSentTo($owner, SubmissionQuotaWarningNotification::class);
    expect($duaList->fresh()->submission_quota_warning_sent_at)->not->toBeNull();

    Notification::fake();

    app(CreateDuaSubmissionAction::class)($duaList->fresh(), [
        'first_name' => 'Guest',
        'email' => 'guest2@example.com',
        'content' => 'Another dua please.',
    ]);

    Notification::assertNotSentTo($owner, SubmissionQuotaWarningNotification::class);
});

test('transactional notifications implement should queue', function () {
    expect(new WelcomeUserNotification)->toBeInstanceOf(ShouldQueue::class);
    expect(new ListCreatedNotification(DuaList::factory()->make()))->toBeInstanceOf(ShouldQueue::class);
    expect(new NewSubmissionNotification(DuaSubmission::factory()->make()))->toBeInstanceOf(ShouldQueue::class);
    expect(new DuaCompletedNotification(DuaSubmission::factory()->make()))->toBeInstanceOf(ShouldQueue::class);
    expect(new SubmissionQuotaWarningNotification(DuaList::factory()->make()))->toBeInstanceOf(ShouldQueue::class);
});

test('email health widget renders delivery metrics', function () {
    $admin = User::factory()->admin()->create();

    EmailSendLog::query()->create([
        'notification_class' => WelcomeUserNotification::class,
        'recipient_email' => 'user@example.com',
        'status' => EmailSendLog::STATUS_SENT,
        'sent_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(EmailHealthWidget::class)
        ->assertSee('Emails Sent Today')
        ->assertSee('Daily Digests Today')
        ->assertSee('Pending Digest Submissions')
        ->assertSee('Failed Emails Today');
});
