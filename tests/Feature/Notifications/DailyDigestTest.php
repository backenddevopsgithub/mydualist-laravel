<?php

use App\Domains\Notifications\Jobs\SendDailyDigestJob;
use App\Domains\Notifications\Notifications\DailyDigestNotification;
use App\Domains\Notifications\Notifications\NewSubmissionNotification;
use App\Domains\Notifications\Services\DailyDigestService;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('daily digest sends one notification per list and marks submissions processed', function () {
    Notification::fake();

    $owner = User::factory()->create(['first_name' => 'Amina']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);

    DuaSubmission::factory()->count(2)->create([
        'dua_list_id' => $duaList->id,
        'is_personal_dua' => false,
        'digest_sent_at' => null,
    ]);

    expect(app(DailyDigestService::class)->sendPendingDigests())->toBe(1);

    Notification::assertSentTo($owner, DailyDigestNotification::class);
    Notification::assertCount(1);

    expect(DuaSubmission::query()->whereNull('digest_sent_at')->count())->toBe(0);
});

test('daily digest groups multiple lists separately', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $firstList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);
    $secondList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $firstList->id,
        'digest_sent_at' => null,
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $secondList->id,
        'digest_sent_at' => null,
    ]);

    expect(app(DailyDigestService::class)->sendPendingDigests())->toBe(2);

    Notification::assertSentTo($owner, DailyDigestNotification::class);
    Notification::assertCount(2);
});

test('daily digest is idempotent and does not resend processed submissions', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'digest_sent_at' => null,
    ]);

    app(DailyDigestService::class)->sendPendingDigests();

    Notification::assertSentTo($owner, DailyDigestNotification::class);

    Notification::fake();

    expect(app(DailyDigestService::class)->sendPendingDigests())->toBe(0);
    Notification::assertNothingSent();
});

test('daily digest job delegates to digest service', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'digest_sent_at' => null,
    ]);

    app(SendDailyDigestJob::class)->handle(app(DailyDigestService::class));

    Notification::assertSentTo($owner, DailyDigestNotification::class);
});

test('daily digest excludes personal duas and every submission lists', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $digestList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'daily_summary',
    ]);
    $immediateList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'every_submission',
    ]);

    DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $digestList->id,
        'digest_sent_at' => null,
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $immediateList->id,
        'digest_sent_at' => null,
    ]);

    expect(app(DailyDigestService::class)->sendPendingDigests())->toBe(0);
    Notification::assertNothingSent();
});

test('new submission on daily summary list queues digest instead of immediate email', function () {
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

    Notification::assertNotSentTo($owner, NewSubmissionNotification::class);
    Notification::assertNothingSent();

    expect(DuaSubmission::query()->first()->digest_sent_at)->toBeNull();
});

test('daily digest notification implements should queue', function () {
    $notification = new DailyDigestNotification(
        DuaList::factory()->make(),
        collect([DuaSubmission::factory()->make()]),
    );

    expect($notification)->toBeInstanceOf(ShouldQueue::class);
});
