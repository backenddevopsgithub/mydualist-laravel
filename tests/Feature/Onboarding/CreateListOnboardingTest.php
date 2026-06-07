<?php

use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Domains\Onboarding\Services\OnboardingState;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('onboarding pages render and guarded steps redirect safely', function () {
    $this->get('/create-list')->assertRedirect(route('onboarding.show', 'account'));

    $this->get('/create-list/account')
        ->assertOk()
        ->assertSee('Create Your Account');

    $this->get('/create-list/dates')
        ->assertRedirect(route('onboarding.show', 'account'));
});

test('onboarding validates account step', function () {
    $this->from('/create-list/account')
        ->post('/create-list/account', [])
        ->assertRedirect('/create-list/account')
        ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password', 'terms']);
});

test('onboarding creates account and persists state between steps', function () {
    Notification::fake();

    $this->post('/create-list/account', [
        'first_name' => 'Onboarding',
        'last_name' => 'User',
        'email' => 'onboarding@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ])->assertRedirect(route('onboarding.show', 'list'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'onboarding@example.com',
        'first_name' => 'Onboarding',
        'last_name' => 'User',
    ]);

    $user = User::query()->where('email', 'onboarding@example.com')->firstOrFail();
    Notification::assertSentTo($user, OnboardingVerificationCodeNotification::class);

    expect(session(OnboardingState::SESSION_KEY.'.user_id'))->toBe($user->id)
        ->and(session(OnboardingState::SESSION_KEY.'.verification_code'))->toHaveLength(4);
});

test('onboarding validates list category and date steps', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'current_step' => 'list',
            ],
        ])
        ->from('/create-list/list')
        ->post('/create-list/list', [])
        ->assertRedirect('/create-list/list')
        ->assertSessionHasErrors(['title']);

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027'],
                'current_step' => 'category',
            ],
        ])
        ->from('/create-list/category')
        ->post('/create-list/category', [])
        ->assertRedirect('/create-list/category')
        ->assertSessionHasErrors('occasion');

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027'],
                'category' => ['occasion' => 'hajj'],
                'current_step' => 'dates',
            ],
        ])
        ->from('/create-list/dates')
        ->post('/create-list/dates', [
            'start_date' => '2027-07-01',
            'end_date' => '2027-06-01',
        ])
        ->assertRedirect('/create-list/dates')
        ->assertSessionHasErrors('end_date');
});

test('onboarding completes end to end and creates first list', function () {
    Notification::fake();
    Storage::fake('public');

    $this->post('/create-list/account', [
        'first_name' => 'Arsalan',
        'last_name' => 'Creator',
        'email' => 'creator@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ])->assertRedirect(route('onboarding.show', 'list'));

    $code = session(OnboardingState::SESSION_KEY.'.verification_code');

    $this->post('/create-list/list', [
        'title' => 'Hajj 2027',
    ])->assertRedirect(route('onboarding.show', 'category'));

    expect(session(OnboardingState::SESSION_KEY.'.list.title'))->toBe('Hajj 2027');

    $this->post('/create-list/category', [
        'occasion' => 'hajj',
    ])->assertRedirect(route('onboarding.show', 'dates'));

    $this->post('/create-list/dates', [
        'start_date' => '2027-06-06',
        'end_date' => '2027-06-30',
    ])->assertRedirect(route('onboarding.show', 'image'));

    $this->post('/create-list/image', [
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
    ])
        ->assertRedirect(route('onboarding.show', 'verify'));

    $path = session(OnboardingState::SESSION_KEY.'.image.cover_image_path');
    expect($path)->toStartWith('list-covers/');
    Storage::disk('public')->assertExists($path);

    $this->post('/create-list/verify', [
        'code' => str_split($code),
    ])->assertRedirect(route('dashboard'));

    $duaList = DuaList::query()->firstOrFail();

    expect($duaList->title)->toBe('Hajj 2027')
        ->and($duaList->occasion)->toBe('hajj')
        ->and($duaList->cover_image_path)->toBe($path)
        ->and($duaList->slug)->toBe("arsalan-hajj-{$duaList->id}")
        ->and($duaList->user->email)->toBe('creator@example.com');

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Hajj 2027');
});

test('onboarding rejects incorrect verification code', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'verification_code' => '1234',
                'list' => ['title' => 'Umrah Trip'],
                'category' => ['occasion' => 'umrah'],
                'dates' => ['start_date' => '2027-01-01', 'end_date' => '2027-01-10'],
                'image' => ['cover_image_path' => null],
                'current_step' => 'verify',
            ],
        ])
        ->from('/create-list/verify')
        ->post('/create-list/verify', [
            'code' => ['0', '0', '0', '0'],
        ])
        ->assertRedirect('/create-list/verify')
        ->assertSessionHasErrors('code');
});

test('category step has no forced default selection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Ramadan Duas'],
                'current_step' => 'category',
            ],
        ])
        ->get('/create-list/category')
        ->assertOk()
        ->assertDontSee('checked', false);
});

test('logged in homepage header shows dashboard without create list cta', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
