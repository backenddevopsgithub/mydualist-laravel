<?php

use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Domains\Notifications\Notifications\DuaCompletedNotification;
use App\Domains\Onboarding\Services\OnboardingState;
use App\Jobs\SendWhatsAppCompletionNotificationJob;
use App\Models\DuaList;
use App\Models\User;
use App\Support\CreatorMode;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Config::set('mydualist.creator_mode.enabled', false);
});

test('creator mode routes and ui are hidden when disabled', function () {
    $this->get('/creator-mode')->assertNotFound();

    $user = User::factory()->create();
    $duaList = DuaList::factory()->creator()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard.profile'))
        ->assertOk()
        ->assertDontSee('Creator Mode Settings');

    $this->get(route('fundraising.redirect', [
        'redirecting' => 'https://www.launchgood.com/test',
        'list_id' => $duaList->id,
        'tracking' => 'native',
        'bypass' => 'false',
    ]))->assertRedirect('https://www.launchgood.com/test');

    expect($duaList->fresh()->insights_clicks)->toBe(0);
});

test('creator mode onboarding and settings are available when enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);

    $user = User::factory()->create();
    $duaList = DuaList::factory()->creator()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/creator-mode')
        ->assertRedirect(route('onboarding.show', 'list'));

    $this->actingAs($user)
        ->get(route('dashboard.profile'))
        ->assertOk()
        ->assertSee('Creator Mode Settings');

    $this->actingAs($user)
        ->patch(route('dashboard.profile.creator-mode'), [
            'dua_list_id' => $duaList->id,
            'donation_link' => 'https://www.launchgood.com/updated-campaign',
            'donation_note' => 'Updated note',
        ])
        ->assertRedirect(route('dashboard.profile', ['tab' => 'list-settings']));

    $duaList->refresh();

    expect($duaList->donation_link)->toBe('https://www.launchgood.com/updated-campaign')
        ->and($duaList->donation_note)->toBe('Updated note');
});

test('fundraising redirect tracks clicks only when creator mode is enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);

    $duaList = DuaList::factory()->creator()->create();

    $this->get(route('fundraising.redirect', [
        'redirecting' => 'https://www.launchgood.com/test',
        'list_id' => $duaList->id,
        'tracking' => 'native',
        'bypass' => 'false',
    ]))->assertRedirect('https://www.launchgood.com/test');

    expect($duaList->fresh()->insights_clicks)->toBe(1);

    Config::set('mydualist.creator_mode.enabled', false);

    $this->get(route('fundraising.redirect', [
        'redirecting' => 'https://www.launchgood.com/test',
        'list_id' => $duaList->id,
        'tracking' => 'native',
        'bypass' => 'false',
    ]))->assertRedirect('https://www.launchgood.com/test');

    expect($duaList->fresh()->insights_clicks)->toBe(1);
});

test('completion email includes fundraising block for creator lists when enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);

    $owner = User::factory()->create(['first_name' => 'Yusuf']);
    $duaList = DuaList::factory()->creator()->create(['user_id' => $owner->id]);
    $submission = \App\Models\DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'email' => 'submitter@example.com',
        'content' => 'Please make dua for my parents.',
    ]);

    $message = (new DuaCompletedNotification($submission))->toMail(
        Notification::route('mail', 'submitter@example.com')
    );

    $html = view($message->view, $message->viewData)->render();

    expect($html)
        ->toContain('Why not show thanks by donating to Yusuf’s latest cause?')
        ->toContain('Support Now')
        ->toContain('Please support this cause.')
        ->not->toContain('donorbox.org');
});

test('completion email omits fundraising block when creator mode is disabled but data remains', function () {
    $owner = User::factory()->create(['first_name' => 'Yusuf']);
    $duaList = DuaList::factory()->creator()->create(['user_id' => $owner->id]);
    $submission = \App\Models\DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'email' => 'submitter@example.com',
        'content' => 'Please make dua for my parents.',
    ]);

    $message = (new DuaCompletedNotification($submission))->toMail(
        Notification::route('mail', 'submitter@example.com')
    );

    $html = view($message->view, $message->viewData)->render();

    expect($html)
        ->toContain('donorbox.org')
        ->not->toContain('Support Now');
});

test('whatsapp completion uses creator template when creator mode is enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);
    Config::set('services.twilio.account_sid', 'ACtest');
    Config::set('services.twilio.auth_token', 'token');
    Config::set('services.twilio.messaging_service_sid', 'MGtest');
    Config::set('services.twilio.whatsapp_from', '+14155238886');
    Config::set('services.twilio.creator_completion_template_sid', 'HXcreator');

    Queue::fake();

    $owner = User::factory()->create(['first_name' => 'Yusuf']);
    $duaList = DuaList::factory()->creator()->create(['user_id' => $owner->id]);
    $submission = \App\Models\DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'whatsapp_verified_at' => now(),
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900000',
    ]);

    SendWhatsAppCompletionNotificationJob::dispatch($submission->id);

    Queue::assertPushed(SendWhatsAppCompletionNotificationJob::class);
});

test('creator list can be created from onboarding state when enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);

    $user = User::factory()->create();

    $duaList = app(CreateDuaListAction::class)($user, [
        'title' => 'Creator Trip',
        'occasion' => 'hajj',
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
        'list_mode' => CreatorMode::MODE_CREATOR,
        'donation_link' => 'https://www.launchgood.com/test-campaign',
        'donation_note' => 'Support our cause',
    ]);

    expect($duaList->list_mode)->toBe('creator')
        ->and($duaList->donation_link)->toBe('https://www.launchgood.com/test-campaign')
        ->and($duaList->donation_note)->toBe('Support our cause');
});

test('onboarding state excludes fundraising step when creator mode is disabled', function () {
    $state = app(OnboardingState::class);
    $state->merge(['creator_mode' => true]);

    expect($state->steps())->not->toContain('fundraising');
});

test('onboarding state includes fundraising step when creator mode is enabled', function () {
    Config::set('mydualist.creator_mode.enabled', true);

    $state = app(OnboardingState::class);
    $state->merge(['creator_mode' => true]);

    expect($state->steps())->toContain('fundraising');
});
