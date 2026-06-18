<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DuaList>
 */
class DuaListFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'title' => Str::headline($title),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'occasion' => 'hajj',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'cover_image_path' => null,
            'status' => 'active',
            'published_at' => now(),
        ];
    }

    public function creator(): static
    {
        return $this->state(fn (array $attributes) => [
            'list_mode' => \App\Support\CreatorMode::MODE_CREATOR,
            'donation_link' => 'https://www.launchgood.com/test-campaign',
            'donation_note' => 'Please support this cause.',
        ]);
    }
}
