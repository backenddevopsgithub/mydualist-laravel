<?php

use App\Domains\Onboarding\Services\OnboardingState;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

function makeOnboardingState(): OnboardingState
{
    $session = new Store('test', new ArraySessionHandler(120));

    return new OnboardingState($session);
}

test('onboarding steps include merged list flow and success screen', function () {
    expect(OnboardingState::STEPS)->toBe([
        'account',
        'list',
        'dates',
        'image',
        'verify',
        'success',
    ]);
});

test('onboarding state tracks next and previous steps', function () {
    $state = makeOnboardingState();

    expect($state->stepIndex('account'))->toBe(0)
        ->and($state->nextStep('account'))->toBe('list')
        ->and($state->previousStep('dates'))->toBe('list')
        ->and($state->displayStepCount())->toBe(5);
});

test('onboarding state merges nested session data', function () {
    $state = makeOnboardingState();

    $state->merge([
        'list' => ['title' => 'Hajj 2027'],
        'current_step' => 'list',
    ]);

    $state->merge([
        'list' => ['occasion' => 'hajj'],
    ]);

    expect($state->get('list.title'))->toBe('Hajj 2027')
        ->and($state->get('list.occasion'))->toBe('hajj')
        ->and($state->currentStep())->toBe('list');
});
