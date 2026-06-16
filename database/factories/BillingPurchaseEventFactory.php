<?php

namespace Database\Factories;

use App\Enums\BillingPurchaseEventType;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPurchaseEvent>
 */
class BillingPurchaseEventFactory extends Factory
{
    protected $model = BillingPurchaseEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'billing_purchase_id' => BillingPurchase::factory(),
            'event_type' => BillingPurchaseEventType::PaymentIntentSucceeded,
            'stripe_event_id' => 'evt_'.fake()->unique()->bothify('????????????????????'),
            'idempotency_key' => null,
            'payload' => ['test' => true],
            'processed_at' => now(),
            'failed_at' => null,
            'error_message' => null,
        ];
    }
}
