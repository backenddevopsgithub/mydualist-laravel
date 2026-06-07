<?php

use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('onboarding verification code requires authentication', function () {
    $this->postJson('/api/v1/onboarding/verification-code')->assertUnauthorized();
});

test('unverified user can request onboarding verification code', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->postJson('/api/v1/onboarding/verification-code')
        ->assertOk()
        ->assertJsonPath('message', 'Verification code sent.');

    Notification::assertSentTo($user, OnboardingVerificationCodeNotification::class);
    expect(Cache::get('onboarding-verify:'.$user->id))->toMatch('/^\d{4}$/');
});

test('verified user cannot request onboarding verification code', function () {
    $this->actingAsUser();

    $this->postJson('/api/v1/onboarding/verification-code')
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'email_already_verified');
});

test('unverified user can verify email with otp', function () {
    $user = User::factory()->unverified()->create();
    Cache::put('onboarding-verify:'.$user->id, '4321');
    $this->actingAsUser($user);

    $this->postJson('/api/v1/onboarding/verify-email', [
        'code' => '4321',
    ])->assertOk()
        ->assertJsonPath('message', 'Email verified successfully.');

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    expect(Cache::get('onboarding-verify:'.$user->id))->toBeNull();
});

test('verify email rejects incorrect code', function () {
    $user = User::factory()->unverified()->create();
    Cache::put('onboarding-verify:'.$user->id, '4321');
    $this->actingAsUser($user);

    $this->postJson('/api/v1/onboarding/verify-email', [
        'code' => '0000',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

test('verified user can create first list via onboarding endpoint', function () {
    Storage::fake('public');

    $user = $this->actingAsUser();

    $response = $this->postJson('/api/v1/onboarding/create-list', [
        'title' => 'Mobile Hajj List',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
    ]);

    $response->assertCreated()
        ->assertJsonPath('message', 'List created successfully.')
        ->assertJsonPath('data.title', 'Mobile Hajj List');

    $list = DuaList::query()->where('user_id', $user->id)->firstOrFail();

    expect($list->occasion)->toBe('hajj')
        ->and($list->cover_image_path)->toStartWith('list-covers/');
});

test('unverified user cannot create list via onboarding endpoint', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->postJson('/api/v1/onboarding/create-list', [
        'title' => 'Blocked List',
        'occasion' => 'hajj',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonth()->toDateString(),
    ])->assertForbidden();
});

test('mobile onboarding flow registers verifies and creates list', function () {
    Notification::fake();
    Storage::fake('public');

    $registration = $this->registerUser([
        'name' => 'Mobile User',
        'email' => 'mobile@example.com',
    ]);

    $user = $registration['user'];
    expect($user->hasVerifiedEmail())->toBeFalse();

    Sanctum::actingAs($user, ['*']);

    $this->postJson('/api/v1/onboarding/verification-code')->assertOk();

    $code = Cache::get('onboarding-verify:'.$user->id);

    $this->postJson('/api/v1/onboarding/verify-email', ['code' => $code])->assertOk();

    $this->postJson('/api/v1/onboarding/create-list', [
        'title' => 'End To End List',
        'occasion' => 'ramadan',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addWeeks(2)->toDateString(),
    ])->assertCreated()
        ->assertJsonPath('data.title', 'End To End List');

    expect(DuaList::query()->where('user_id', $user->id)->count())->toBe(1);
});
