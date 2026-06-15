<?php

namespace Database\Factories;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommunityDua>
 */
class CommunityDuaFactory extends Factory
{
    protected $model = CommunityDua::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CommunityDuaType::cases());

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'gender' => fake()->randomElement(['male', 'female']),
            'content' => fake()->sentence(12),
            'type' => $type,
            'status' => CommunityDuaStatus::Active,
            'required_completions' => $type->requiredCompletions(),
            'completion_count' => 0,
            'is_visible' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (): array => [
            'type' => CommunityDuaType::Free,
            'required_completions' => 1,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'type' => CommunityDuaType::Paid,
            'required_completions' => 20,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $required = (int) ($attributes['required_completions'] ?? 1);

            return [
                'status' => CommunityDuaStatus::Completed,
                'completion_count' => $required,
                'is_visible' => false,
                'fulfilled_at' => now(),
            ];
        });
    }
}
