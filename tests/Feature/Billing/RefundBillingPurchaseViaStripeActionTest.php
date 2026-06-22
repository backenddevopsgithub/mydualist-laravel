<?php

use App\Domains\Billing\Actions\RefundBillingPurchaseViaStripeAction;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

test('refund billing purchase via stripe action creates stripe refund and records locally', function () {
    $admin = User::factory()->admin()->create();
    $product = BillingProduct::query()->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'user_id' => $admin->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'payment_intent_id' => 'pi_refund_admin_001',
        'fulfilled_at' => now(),
        'amount_minor' => 799,
        'currency' => 'gbp',
    ]);

    app()->instance(StripePaymentIntentService::class, new class extends StripePaymentIntentService
    {
        public function refundPaymentIntent(string $paymentIntentId, ?int $amountMinor = null): array
        {
            expect($paymentIntentId)->toBe('pi_refund_admin_001');

            return [
                'id' => 're_admin_001',
                'amount' => 799,
                'status' => 'succeeded',
            ];
        }
    });

    app(RefundBillingPurchaseViaStripeAction::class)($purchase, $admin);

    expect($purchase->fresh()->refunded_at)->not->toBeNull()
        ->and(BillingPurchaseEvent::query()
            ->where('billing_purchase_id', $purchase->id)
            ->where('event_type', BillingPurchaseEventType::AdminStripeRefund)
            ->exists())->toBeTrue();
});

test('refund billing purchase via stripe action rejects purchases without payment intent', function () {
    $product = BillingProduct::query()->firstOrFail();

    $purchase = BillingPurchase::factory()->create([
        'billing_product_id' => $product->id,
        'status' => BillingPurchaseStatus::Succeeded,
        'wp_order_id' => 12345,
        'payment_intent_id' => null,
    ]);

    app(RefundBillingPurchaseViaStripeAction::class)($purchase);
})->throws(RuntimeException::class);
