<?php

namespace Database\Factories;

use App\Models\DuaSuggestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DuaSuggestion>
 */
class DuaSuggestionFactory extends Factory
{
    protected $model = DuaSuggestion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(['hajj', 'umrah', 'ramadan', 'family', 'health', 'forgiveness', 'guidance', 'other', '']),
            'content' => fake()->paragraph(),
            'source_type' => fake()->randomElement(['general', 'quran', 'sunnah']),
            'source_reference' => null,
            'is_visible' => true,
            'sort_order' => 0,
            'used_count' => 0,
        ];
    }

    public function global(): static
    {
        return $this->state(fn (): array => [
            'category' => '',
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (): array => [
            'is_visible' => false,
        ]);
    }
}
