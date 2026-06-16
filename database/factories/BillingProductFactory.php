<?php

namespace Database\Factories;

use App\Enums\BillingProductScope;
use App\Models\BillingProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingProduct>
 */
class BillingProductFactory extends Factory
{
    protected $model = BillingProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'TEST_PRODUCT_'.fake()->unique()->numerify('###'),
            'external_product_id' => fake()->unique()->numberBetween(9000, 9999),
            'name' => fake()->words(3, true),
            'scope' => BillingProductScope::User,
            'stackable' => false,
            'requires_authentication' => true,
            'amount_minor' => 799,
            'currency' => 'gbp',
            'active' => true,
            'metadata' => null,
        ];
    }
}
