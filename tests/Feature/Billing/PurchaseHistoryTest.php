<?php

use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\User;

test('authenticated users can view their purchase history', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $visible = BillingPurchase::factory()->succeeded()->for($user)->create([
        'amount_minor' => 1199,
        'currency' => 'gbp',
    ]);

    BillingPurchase::factory()->succeeded()->for($otherUser)->create();

    $this->actingAs($user)
        ->get(route('dashboard.purchases'))
        ->assertOk()
        ->assertSee('Purchase History')
        ->assertSee($visible->product->name)
        ->assertSee('£11.99');
});

test('guests cannot view purchase history', function () {
    $this->get(route('dashboard.purchases'))
        ->assertRedirect();
});

test('billing health command reports snapshot', function () {
    config(['billing.monitoring.unfulfilled_purchase_alert_threshold' => 1]);

    BillingPurchase::factory()->create([
        'status' => BillingPurchaseStatus::Succeeded,
        'fulfilled_at' => null,
        'payment_intent_id' => 'pi_health_001',
    ]);

    $this->artisan('billing:health')
        ->assertExitCode(1)
        ->expectsOutputToContain('unfulfilled_succeeded_purchases');
});
