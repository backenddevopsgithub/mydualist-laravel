<?php

use App\Domains\Notifications\Jobs\SendClosingSoonReminderJob;
use App\Domains\Notifications\Jobs\SendListImageReminderJob;
use App\Domains\Notifications\Jobs\SendNoActivityReminderJob;
use App\Domains\Notifications\Notifications\ClosingSoonReminderNotification;
use App\Domains\Notifications\Notifications\ListImageReminderNotification;
use App\Domains\Notifications\Notifications\NoActivityReminderNotification;
use App\Domains\Notifications\Services\ReminderEmailService;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

test('no activity reminder sends after twenty four hours without submissions', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 12:00:00');

    $owner = User::factory()->create(['first_name' => 'Amina']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'published_at' => now()->subHours(25),
    ]);

    expect(app(ReminderEmailService::class)->sendNoActivityReminders())->toBe(1);

    Notification::assertSentTo($owner, NoActivityReminderNotification::class);
    expect($duaList->fresh()->no_activity_reminder_sent_at)->not->toBeNull();
});

test('no activity reminder waits until twenty four hours have passed', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 12:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'published_at' => now()->subHours(12),
    ]);

    expect(app(ReminderEmailService::class)->sendNoActivityReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('no activity reminder is skipped when list has submissions', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 12:00:00');

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'published_at' => now()->subHours(30),
    ]);

    DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    expect(app(ReminderEmailService::class)->sendNoActivityReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('no activity reminder is not duplicated once sent', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 12:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'published_at' => now()->subHours(30),
        'no_activity_reminder_sent_at' => now()->subHour(),
    ]);

    expect(app(ReminderEmailService::class)->sendNoActivityReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('closing soon reminder sends within three hours of end date without submissions', function () {
    Notification::fake();

    $this->travelTo('2026-06-17 22:00:00');

    $owner = User::factory()->create(['first_name' => 'Yusuf']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'end_date' => '2026-06-18',
    ]);

    expect(app(ReminderEmailService::class)->sendClosingSoonReminders())->toBe(1);

    Notification::assertSentTo($owner, ClosingSoonReminderNotification::class);
    expect($duaList->fresh()->closing_soon_reminder_sent_at)->not->toBeNull();
});

test('closing soon reminder waits until three hours before end date', function () {
    Notification::fake();

    $this->travelTo('2026-06-15 12:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'end_date' => '2026-06-18',
    ]);

    expect(app(ReminderEmailService::class)->sendClosingSoonReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('closing soon reminder is skipped when list has submissions', function () {
    Notification::fake();

    $this->travelTo('2026-06-17 22:00:00');

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'end_date' => '2026-06-18',
    ]);

    DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    expect(app(ReminderEmailService::class)->sendClosingSoonReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('closing soon reminder is not duplicated once sent', function () {
    Notification::fake();

    $this->travelTo('2026-06-17 22:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'end_date' => '2026-06-18',
        'closing_soon_reminder_sent_at' => now()->subHour(),
    ]);

    expect(app(ReminderEmailService::class)->sendClosingSoonReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('list image reminder sends after one hour from start date without cover image', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 02:00:00');

    $owner = User::factory()->create(['first_name' => 'Fatima']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'start_date' => '2026-06-18',
        'end_date' => '2026-07-18',
        'cover_image_path' => null,
    ]);

    expect(app(ReminderEmailService::class)->sendListImageReminders())->toBe(1);

    Notification::assertSentTo($owner, ListImageReminderNotification::class);
    expect($duaList->fresh()->list_image_reminder_sent_at)->not->toBeNull();
});

test('list image reminder waits until one hour after start date', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 00:30:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'start_date' => '2026-06-18',
        'end_date' => '2026-07-18',
        'cover_image_path' => null,
    ]);

    expect(app(ReminderEmailService::class)->sendListImageReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('list image reminder is skipped when cover image exists', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 10:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-07-18',
        'cover_image_path' => 'list-covers/example.jpg',
    ]);

    expect(app(ReminderEmailService::class)->sendListImageReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('list image reminder is not duplicated once sent', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 10:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'start_date' => '2026-06-10',
        'end_date' => '2026-07-18',
        'cover_image_path' => null,
        'list_image_reminder_sent_at' => now()->subHour(),
    ]);

    expect(app(ReminderEmailService::class)->sendListImageReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('reminder notifications are queued', function () {
    expect(new NoActivityReminderNotification(DuaList::factory()->make()))
        ->toBeInstanceOf(ShouldQueue::class);
    expect(new ClosingSoonReminderNotification(DuaList::factory()->make()))
        ->toBeInstanceOf(ShouldQueue::class);
    expect(new ListImageReminderNotification(DuaList::factory()->make()))
        ->toBeInstanceOf(ShouldQueue::class);
});

test('no activity reminder job delegates to reminder email service', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 12:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'published_at' => now()->subHours(30),
    ]);

    app(SendNoActivityReminderJob::class)->handle(app(ReminderEmailService::class));

    Notification::assertSentTo($owner, NoActivityReminderNotification::class);
});

test('closing soon reminder job delegates to reminder email service', function () {
    Notification::fake();

    $this->travelTo('2026-06-17 22:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'end_date' => '2026-06-18',
    ]);

    app(SendClosingSoonReminderJob::class)->handle(app(ReminderEmailService::class));

    Notification::assertSentTo($owner, ClosingSoonReminderNotification::class);
});

test('list image reminder job delegates to reminder email service', function () {
    Notification::fake();

    $this->travelTo('2026-06-18 02:00:00');

    $owner = User::factory()->create();
    DuaList::factory()->create([
        'user_id' => $owner->id,
        'start_date' => '2026-06-18',
        'end_date' => '2026-07-18',
        'cover_image_path' => null,
    ]);

    app(SendListImageReminderJob::class)->handle(app(ReminderEmailService::class));

    Notification::assertSentTo($owner, ListImageReminderNotification::class);
});

test('scheduled reminder jobs are registered', function () {
    $this->artisan('schedule:list')
        ->assertSuccessful()
        ->expectsOutputToContain('send-no-activity-reminder')
        ->expectsOutputToContain('send-closing-soon-reminder')
        ->expectsOutputToContain('send-list-image-reminder');
});
