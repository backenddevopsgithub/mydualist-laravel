<?php

namespace Database\Factories;

use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BillingPurchase>
 */
class BillingPurchaseFactory extends Factory
{
    protected $model = BillingPurchase::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billing_product_id' => BillingProduct::factory(),
            'user_id' => User::factory(),
            'dua_list_id' => null,
            'community_dua_id' => null,
            'status' => BillingPurchaseStatus::RequiresPaymentMethod,
            'payment_intent_id' => null,
            'amount_minor' => 799,
            'currency' => 'gbp',
            'idempotency_key' => (string) Str::uuid(),
            'fulfilled_at' => null,
            'refunded_at' => null,
            'disputed_at' => null,
            'failure_reason' => null,
            'metadata' => null,
        ];
    }

    public function succeeded(): static
    {
        return $this->state(fn (): array => [
            'status' => BillingPurchaseStatus::Succeeded,
            'payment_intent_id' => 'pi_'.fake()->unique()->bothify('????????????????????'),
            'fulfilled_at' => now(),
        ]);
    }
}
