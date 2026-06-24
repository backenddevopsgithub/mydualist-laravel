<?php

use App\Domains\Onboarding\Notifications\OnboardingVerificationCodeNotification;
use App\Domains\Onboarding\Services\OnboardingState;
use App\Models\DuaList;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        ->assertSessionHasErrors(['first_name', 'last_name', 'gender', 'email', 'password', 'terms']);
});

test('onboarding creates account and persists state between steps', function () {
    Notification::fake();

    $this->post('/create-list/account', [
        'first_name' => 'Onboarding',
        'last_name' => 'User',
        'gender' => 'male',
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
        'gender' => 'male',
    ]);

    $user = User::query()->where('email', 'onboarding@example.com')->firstOrFail();
    Notification::assertNothingSent();

    expect(session(OnboardingState::SESSION_KEY.'.user_id'))->toBe($user->id)
        ->and(session(OnboardingState::SESSION_KEY.'.verification_code'))->toBeNull()
        ->and($user->tokens()->count())->toBe(0);
});

test('onboarding validates list and date steps', function () {
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
        ->assertSessionHasErrors(['title', 'occasion']);

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027', 'occasion' => 'hajj'],
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
        'gender' => 'male',
        'email' => 'creator@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
        'terms' => '1',
    ])->assertRedirect(route('onboarding.show', 'list'));

    $this->post('/create-list/list', [
        'title' => 'Hajj 2027',
        'occasion' => 'hajj',
    ])->assertRedirect(route('onboarding.show', 'dates'));

    expect(session(OnboardingState::SESSION_KEY.'.list.title'))->toBe('Hajj 2027')
        ->and(session(OnboardingState::SESSION_KEY.'.list.occasion'))->toBe('hajj');

    $this->post('/create-list/dates', [
        'start_date' => '2027-06-06',
        'end_date' => '2027-06-30',
    ])->assertRedirect(route('onboarding.show', 'image'));

    $this->post('/create-list/image', [
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
    ])
        ->assertRedirect(route('onboarding.show', 'verify'));

    $user = User::query()->where('email', 'creator@example.com')->firstOrFail();
    Notification::assertSentTo($user, OnboardingVerificationCodeNotification::class);

    $code = session(OnboardingState::SESSION_KEY.'.verification_code');
    $path = session(OnboardingState::SESSION_KEY.'.image.cover_image_path');
    expect($path)->toStartWith('list-covers/');
    Storage::disk('public')->assertExists($path);

    $this->post('/create-list/verify', [
        'code' => str_split($code),
    ])->assertRedirect(route('onboarding.show', 'success'));

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

test('verified logged in user creates another list without otp', function () {
    Notification::fake();
    Storage::fake('public');

    $user = User::factory()->create([
        'first_name' => 'Arsalan',
        'last_name' => 'Creator',
    ]);

    DuaList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/create-list')
        ->assertRedirect(route('onboarding.show', 'list'));

    Notification::assertNothingSent();

    $this->post('/create-list/list', [
        'title' => 'Umrah 2028',
        'occasion' => 'umrah',
    ])->assertRedirect(route('onboarding.show', 'dates'));

    $this->post('/create-list/dates', [
        'start_date' => '2028-01-01',
        'end_date' => '2028-01-15',
    ])->assertRedirect(route('onboarding.show', 'image'));

    $this->post('/create-list/image', [
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
    ])->assertRedirect(route('onboarding.show', 'success'));

    expect(DuaList::query()->where('user_id', $user->id)->count())->toBe(2);

    $createdList = DuaList::query()->where('title', 'Umrah 2028')->firstOrFail();

    expect($createdList->slug)->toBe("arsalan-umrah-{$createdList->id}");
});

test('onboarding rejects incorrect verification code', function () {
    $user = User::factory()->create();

    Cache::put('onboarding-verify:'.$user->id, '1234');

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'verification_code' => '1234',
                'list' => ['title' => 'Umrah Trip', 'occasion' => 'umrah'],
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

test('list step has no forced default occasion selection', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'current_step' => 'list',
            ],
        ])
        ->get('/create-list/list')
        ->assertOk()
        ->assertSee('x-model="occasion"', false)
        ->assertSee("occasion: '',", false)
        ->assertSee('! canSubmit', false);
});

test('dates step includes bundled date picker initializers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027', 'occasion' => 'hajj'],
                'current_step' => 'dates',
            ],
        ])
        ->get('/create-list/dates')
        ->assertOk()
        ->assertSee('id="start_date"', false)
        ->assertSee('id="end_date"', false)
        ->assertSee('x-model="startDate"', false)
        ->assertSee('x-model="endDate"', false)
        ->assertSee('! canSubmit', false)
        ->assertSee('ui-date-input', false)
        ->assertDontSee('cdn.jsdelivr.net/npm/flatpickr', false);
});

test('logged in homepage header shows dashboard and community dua cta', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertOk()
        ->assertSee('Submit Community Dua')
        ->assertSee('Dashboard');

    $header = Str::before($response->getContent(), '</header>');

    expect($header)->toContain('Submit Community Dua')
        ->and($header)->toContain('Dashboard')
        ->and($header)->not->toContain('Create a Dua List');
});
