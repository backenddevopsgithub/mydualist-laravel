<?php

namespace App\Domains\Onboarding\Services;

use App\Services\Service;
use App\Support\CreatorMode;
use Illuminate\Session\Store;

class OnboardingState extends Service
{
    public const SESSION_KEY = 'onboarding.create_list';

    public const STEPS = [
        'account',
        'list',
        'dates',
        'image',
        'fundraising',
        'verify',
        'success',
    ];

    public function __construct(
        private readonly Store $session,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function merge(array $data): void
    {
        $this->session->put(self::SESSION_KEY, array_replace_recursive($this->all(), $data));
    }

    public function reset(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * @return list<string>
     */
    public function steps(): array
    {
        if ($this->isCreatorMode()) {
            return self::STEPS;
        }

        return array_values(array_filter(
            self::STEPS,
            fn (string $step): bool => $step !== 'fundraising',
        ));
    }

    public function isCreatorMode(): bool
    {
        return CreatorMode::enabled() && (bool) $this->get('creator_mode', false);
    }

    public function stepIndex(string $step): int
    {
        $index = array_search($step, $this->steps(), true);

        return $index === false ? 0 : $index;
    }

    public function nextStep(string $step): ?string
    {
        $steps = $this->steps();

        return $steps[$this->stepIndex($step) + 1] ?? null;
    }

    public function previousStep(string $step): ?string
    {
        $steps = $this->steps();

        return $steps[$this->stepIndex($step) - 1] ?? null;
    }

    public function currentStep(): string
    {
        return (string) $this->get('current_step', 'account');
    }

    public function displayStepCount(): int
    {
        return count($this->steps()) - 1;
    }
}
