<?php

namespace Database\Factories;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DuaSubmission>
 */
class DuaSubmissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dua_list_id' => DuaList::factory(),
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'gender' => fake()->randomElement(['male', 'female']),
            'is_anonymous' => false,
            'is_personal_dua' => false,
            'content' => fake()->sentence(12),
            'note' => null,
            'status' => DuaSubmission::STATUS_PENDING,
            'completed_at' => null,
            'hidden_at' => null,
            'archived_at' => null,
            'reported_at' => null,
        ];
    }

    public function personal(): static
    {
        return $this->state(fn (): array => [
            'is_personal_dua' => true,
        ]);
    }
}
