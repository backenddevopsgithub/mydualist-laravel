<?php

use App\Domains\Onboarding\Services\OnboardingState;
use App\Models\User;

test('onboarding stores valid trip dates in session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession([
            OnboardingState::SESSION_KEY => [
                'user_id' => $user->id,
                'list' => ['title' => 'Hajj 2027', 'occasion' => 'hajj'],
                'current_step' => 'dates',
            ],
        ])
        ->post('/create-list/dates', [
            'start_date' => '2027-06-06',
            'end_date' => '2027-06-30',
        ])
        ->assertRedirect(route('onboarding.show', 'image'));

    expect(session(OnboardingState::SESSION_KEY.'.dates.start_date'))->toBe('2027-06-06')
        ->and(session(OnboardingState::SESSION_KEY.'.dates.end_date'))->toBe('2027-06-30')
        ->and(session(OnboardingState::SESSION_KEY.'.current_step'))->toBe('image');
});
