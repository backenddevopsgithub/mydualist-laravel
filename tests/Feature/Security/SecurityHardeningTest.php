<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('login attempts are rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
            ->post(route('login.store'), [
                'email' => 'ratelimit@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.10'])
        ->post(route('login.store'), [
            'email' => 'ratelimit@example.com',
            'password' => 'wrong-password',
        ])
        ->assertTooManyRequests();
});

test('public submissions are throttled by ip', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    for ($i = 0; $i < 8; $i++) {
        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.20'])
            ->post(route('dua-lists.submissions.store', $duaList), [
                'content' => "Please make dua for rate limit test {$i}.",
            ])
            ->assertRedirect(route('dua-lists.public', $duaList));
    }

    $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.20'])
        ->post(route('dua-lists.submissions.store', $duaList), [
            'content' => 'Please make dua for rate limit overflow.',
        ])
        ->assertTooManyRequests();
});

test('public submission spam guard blocks honeypot duplicate and link abuse', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'content' => 'Please make dua for our family.',
            'website' => 'https://bot.example',
        ])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('website');

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'duas' => [
                'Please make dua for unique duplicate testing.',
                'Please make dua for unique duplicate testing.',
            ],
        ])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('duas');

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'content' => 'Visit https://one.example and https://two.example and https://three.example',
        ])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('duas');
});

test('dashboard billing and support require verified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('onboarding.start'));

    $this->actingAs($user)
        ->post(route('billing.checkout'))
        ->assertRedirect(route('onboarding.start'));

    $this->actingAs($user)
        ->post(route('dashboard.support.store'), [
            'reason' => 'bug',
            'email' => $user->email,
            'first_name' => 'Amina',
            'surname' => 'Khan',
            'comments' => 'The page is not loading.',
        ])
        ->assertRedirect(route('onboarding.start'));
});

test('normal users cannot access filament admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});

test('image uploads reject invalid extensions even with image mime', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->from(route('dashboard.profile'))
        ->post(route('dashboard.profile.list-image'), [
            'dua_list_id' => $duaList->id,
            'cover_image' => UploadedFile::fake()->create('shell.php', 1, 'image/png'),
        ])
        ->assertRedirect(route('dashboard.profile'))
        ->assertSessionHasErrors('cover_image');
});

test('security headers are added to browser responses', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
        ->assertHeader('Content-Security-Policy');

    expect($this->get(route('home'))->headers->get('Content-Security-Policy'))
        ->toContain("'unsafe-eval'");
});

test('submission policy prevents managing another owners submission', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($user)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertForbidden();
});
