<?php

use App\Domains\Notifications\Notifications\NewSubmissionNotification;
use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Domains\Onboarding\Services\OnboardingVerificationService;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\DuaSuggestion;
use App\Models\User;
use App\Support\PublicSubmissionIdempotency;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

test('registration sends exactly one otp email and no verification link email', function () {
    Notification::fake();
    Event::fake([Registered::class]);

    $this->post('/create-list/account', [
        'first_name' => 'Otp',
        'last_name' => 'Only',
        'gender' => 'male',
        'email' => 'otp-only@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ])->assertRedirect(route('onboarding.show', 'list'));

    $user = User::query()->where('email', 'otp-only@example.com')->firstOrFail();

    Notification::assertSentToTimes($user, OnboardingVerificationCodeNotification::class, 1);
    Notification::assertNotSentTo($user, \App\Domains\Auth\Notifications\VerifyEmailNotification::class);
});

test('unverified login path sends at most one otp email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'email' => 'login-otp@example.com',
        'password' => 'Password123!',
    ]);

    $this->post(route('login.store'), [
        'email' => 'login-otp@example.com',
        'password' => 'Password123!',
    ])->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();

    $this->get(route('onboarding.start'))->assertRedirect(route('onboarding.show', 'list'));

    Notification::assertSentToTimes($user, OnboardingVerificationCodeNotification::class, 1);

    $this->get(route('onboarding.start'))->assertRedirect(route('onboarding.show', 'list'));

    Notification::assertSentToTimes($user, OnboardingVerificationCodeNotification::class, 1);
});

test('onboarding verification service does not resend while code is still valid', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $service = app(OnboardingVerificationService::class);

    expect($service->sendIfNeeded($user))->toHaveLength(4);
    expect($service->sendIfNeeded($user))->toBeNull();

    Notification::assertSentToTimes($user, OnboardingVerificationCodeNotification::class, 1);
});

test('legacy imported global suggestions with general category are visible', function () {
    $list = DuaList::factory()->create([
        'slug' => 'qa-suggestions-list',
        'occasion' => 'hajj',
    ]);

    $suggestion = DuaSuggestion::factory()->create([
        'title' => 'Imported global dua',
        'category' => 'general',
        'source_type' => 'general',
        'is_visible' => true,
    ]);

    $this->getJson('/api/v1/public/lists/qa-suggestions-list/suggestions')
        ->assertOk()
        ->assertJsonPath('data.general.0.id', $suggestion->id);
});

test('public submission batch is idempotent by batch key', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'every_submission',
    ]);

    $batchKey = (string) Str::uuid();
    $payload = [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => ['Please make dua for my parents.', 'Please grant her success.'],
        'submission_batch_key' => $batchKey,
    ];

    app(CreateDuaSubmissionAction::class)($duaList, $payload);
    app(CreateDuaSubmissionAction::class)($duaList, $payload);

    expect(DuaSubmission::query()->where('submission_batch_key', $batchKey)->count())->toBe(2);
    Notification::assertSentToTimes($owner, NewSubmissionNotification::class, 2);
});

test('duplicate public submission refresh cannot duplicate owner notifications', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'email_frequency' => 'every_submission',
    ]);

    $batchKey = (string) Str::uuid();
    PublicSubmissionIdempotency::remember($batchKey, $duaList, 2);

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => ['Please make dua for my parents.', 'Please grant her success.'],
        'submission_batch_key' => $batchKey,
    ])->assertRedirect();

    Notification::assertNothingSent();
    expect(DuaSubmission::query()->count())->toBe(0);
});

test('submission transactional email listener is queued', function () {
    expect(new \App\Listeners\SendSubmissionTransactionalEmails(app(\App\Domains\Notifications\Services\TransactionalEmailService::class)))
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);

    expect(new \App\Listeners\SyncMailchimpOnSubmissionsCreated())
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);

    expect(new \App\Jobs\ProcessDuaSubmissionsCreatedSideEffects(1, [1], 0))
        ->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
});

test('my submissions excludes requests received on owned lists', function () {
    $owner = User::factory()->create(['email' => 'owner@example.com']);
    $submitter = User::factory()->create(['email' => 'submitter@example.com']);
    $ownedList = DuaList::factory()->create(['user_id' => $owner->id, 'title' => 'My Hajj List']);
    $otherList = DuaList::factory()->create(['user_id' => $submitter->id, 'title' => 'Friend List']);

    DuaSubmission::factory()->create([
        'dua_list_id' => $ownedList->id,
        'user_id' => null,
        'email' => 'guest@example.com',
        'content' => 'Received on my list',
    ]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $otherList->id,
        'user_id' => $owner->id,
        'email' => 'owner@example.com',
        'content' => 'Submitted by me elsewhere',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.submissions'))
        ->assertOk()
        ->assertSee('Submitted by me elsewhere')
        ->assertDontSee('Received on my list');
});

test('dashboard submission completion returns json without full page redirect', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->patchJson(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});
