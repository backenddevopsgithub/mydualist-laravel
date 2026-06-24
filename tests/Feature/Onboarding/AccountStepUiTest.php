<?php

test('account step renders alpine submit gating and wise button classes', function () {
    $response = $this->get('/create-list/account');

    $response->assertOk()
        ->assertSee('Create Your Account')
        ->assertSee('Get started', false)
        ->assertSee('ui-label-required', false)
        ->assertSee('First Name', false)
        ->assertSee('Password must contain at least 1 capital letter, 1 special character, and be at least 8 characters long.', false)
        ->assertSee('I agree to the terms and conditions, and I consent to the processing of my personal data in accordance with the Privacy Policy, as required by GDPR.', false)
        ->assertSee('x-data', false)
        ->assertSee('canSubmit', false)
        ->assertSee('passwordMeetsRequirements', false)
        ->assertSee('x-bind:disabled="! canSubmit"', false)
        ->assertSee('x-model="firstName"', false)
        ->assertSee('x-model="lastName"', false)
        ->assertSee('x-model="email"', false)
        ->assertSee('x-model="password"', false)
        ->assertSee('x-model="passwordConfirmation"', false)
        ->assertSee('x-model="gender"', false)
        ->assertSee('x-model="terms"', false)
        ->assertSee('ui-btn--primary', false)
        ->assertSee('disabled', false);
});

test('account step shows validation errors and keeps submit gating markup', function () {
    $this->from('/create-list/account')
        ->post('/create-list/account', [])
        ->assertRedirect('/create-list/account')
        ->assertSessionHasErrors(['first_name', 'last_name', 'gender', 'email', 'password', 'terms']);

    $this->get('/create-list/account')
        ->assertOk()
        ->assertSee('ui-input--error', false)
        ->assertSee('x-bind:disabled="! canSubmit"', false);
});

test('image step shows upload helper copy from production', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            \App\Domains\Onboarding\Services\OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027', 'occasion' => 'hajj'],
                'dates' => ['start_date' => '2027-06-06', 'end_date' => '2027-06-30'],
                'current_step' => 'image',
            ],
        ])
        ->get('/create-list/image')
        ->assertOk()
        ->assertSee("Upload an image that we'll include in confirmation emails when you complete Duas.", false)
        ->assertSee('We can also remind you closer to your start date.', false)
        ->assertSee('This is optional.', false);
});
