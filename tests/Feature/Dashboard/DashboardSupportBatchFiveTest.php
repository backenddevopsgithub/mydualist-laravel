<?php

use App\Domains\Support\Notifications\SupportTicketAcknowledgementNotification;
use App\Domains\Support\Notifications\SupportTicketReceivedNotification;
use App\Domains\Support\Services\SupportNotificationSettings;
use App\Models\AppSetting;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

test('support notification settings normalize and deduplicate recipients', function () {
    $settings = app(SupportNotificationSettings::class);

    expect($settings->normalizeRecipients([
        'Arsalan@ThePilgrim.co',
        'arsalan@thepilgrim.co',
        'support@mydualist.com',
        'invalid-email',
    ]))->toBe([
        'arsalan@thepilgrim.co',
        'support@mydualist.com',
    ]);
});

test('support notification settings fall back to default recipient', function () {
    $settings = app(SupportNotificationSettings::class);

    expect($settings->recipients())->toBe(['arsalan@thepilgrim.co']);
});

test('support notification settings persist recipients in app settings', function () {
    $settings = app(SupportNotificationSettings::class);

    $settings->saveRecipients([
        'support@mydualist.com',
        'team@mydualist.com',
    ]);

    expect(AppSetting::query()->where('key', SupportNotificationSettings::KEY)->exists())->toBeTrue()
        ->and($settings->recipients())->toBe([
            'support@mydualist.com',
            'team@mydualist.com',
        ]);
});

test('support ticket submission notifies configured recipients and user', function () {
    Notification::fake();
    Storage::fake('public');

    app(SupportNotificationSettings::class)->saveRecipients([
        'support@mydualist.com',
        'team@mydualist.com',
    ]);

    $user = User::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
    ]);

    $this->actingAs($user)
        ->post(route('dashboard.support.store'), [
            'reason' => 'bug',
            'email' => 'amina@example.com',
            'first_name' => 'Amina',
            'surname' => 'Khan',
            'comments' => 'The page is not loading correctly.',
            'image' => UploadedFile::fake()->image('bug.png', 800, 600),
        ])
        ->assertRedirect(route('dashboard.support'));

    $ticket = SupportTicket::query()->where('user_id', $user->id)->firstOrFail();

    Notification::assertSentOnDemand(
        SupportTicketReceivedNotification::class,
        fn ($notification, $channels, $notifiable) => in_array($notifiable->routes['mail'] ?? null, [
            'support@mydualist.com',
            'team@mydualist.com',
        ], true) && $notification->toMail($notifiable)->subject === 'New Support Request Received',
    );

    Notification::assertSentOnDemand(
        SupportTicketAcknowledgementNotification::class,
        fn ($notification, $channels, $notifiable) => ($notifiable->routes['mail'] ?? null) === 'amina@example.com',
    );

    expect(SupportTicket::query()->whereKey($ticket->id)->exists())->toBeTrue();
});

test('dashboard sidebar shows user lists logout and upgrade copy', function () {
    $user = User::factory()->create();
    $list = \App\Models\DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Hajj 2027',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('All Lists', false)
        ->assertSee('Hajj 2027', false)
        ->assertSee('Log Out', false)
        ->assertSee("You're on the free plan. Upgrade to unlock more features and receive unlimited dua requests.", false);

    $this->actingAs($user)
        ->get(route('dashboard.lists.show', $list))
        ->assertOk()
        ->assertSee('Hajj 2027', false);
});
