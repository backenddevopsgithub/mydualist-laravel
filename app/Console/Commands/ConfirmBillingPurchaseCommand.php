<?php

namespace App\Console\Commands;

use App\Domains\Billing\Services\BillingPurchaseWebhookService;
use App\Domains\Billing\Services\StripePaymentIntentService;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use Illuminate\Console\Command;
use RuntimeException;
use Stripe\Event;
use Stripe\PaymentIntent;

class ConfirmBillingPurchaseCommand extends Command
{
    protected $signature = 'billing:confirm-purchase
        {--purchase= : Billing purchase ID}
        {--payment-intent= : Stripe Payment Intent ID (pi_...)}
        {--stripe-only : Confirm in Stripe only; do not process the webhook locally}
        {--local-only : Skip Stripe confirmation and process payment_intent.succeeded locally (for legacy Payment Intents)}';

    protected $description = 'Confirm an existing test-mode Payment Intent and optionally process payment_intent.succeeded locally (local/testing only)';

    public function handle(
        StripePaymentIntentService $paymentIntents,
        BillingPurchaseWebhookService $webhooks,
    ): int {
        if (! app()->environment('local', 'testing')) {
            $this->error('This command is only available in the local and testing environments.');

            return self::FAILURE;
        }

        $purchase = $this->resolvePurchase();

        if ($purchase === null) {
            return self::FAILURE;
        }

        $purchase->load(['product', 'user']);

        $paymentIntentId = (string) $purchase->payment_intent_id;

        if ($paymentIntentId === '') {
            $this->error('Purchase does not have a payment_intent_id.');

            return self::FAILURE;
        }

        $this->line("Purchase #{$purchase->id} ({$purchase->product?->code})");
        $this->line("Payment Intent: {$paymentIntentId}");

        try {
            $intent = $paymentIntents->retrieveIntent($paymentIntentId);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('local-only')) {
            return $this->finishAfterIntent($webhooks, $purchase, $this->intentForLocalProcessing($paymentIntents, $intent));
        }

        if (
            $paymentIntents->usesRedirectPaymentMethods($intent)
            && $intent->status !== 'succeeded'
        ) {
            $this->warn('This Payment Intent was created before allow_redirects=never and cannot be confirmed with pm_card_visa.');
            $this->warn('Create a new purchase, or rerun with --local-only to simulate fulfillment locally.');

            return self::FAILURE;
        }

        try {
            $intent = $this->confirmPaymentIntent($paymentIntents, $paymentIntentId, $intent);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        return $this->finishAfterIntent($webhooks, $purchase, $intent);
    }

    private function finishAfterIntent(
        BillingPurchaseWebhookService $webhooks,
        BillingPurchase $purchase,
        PaymentIntent $intent,
    ): int {
        $this->info("Stripe status: {$intent->status}");

        if ($intent->status !== 'succeeded') {
            $this->warn('Payment Intent is not succeeded yet. Fulfillment was not processed.');

            return self::FAILURE;
        }

        if ($this->option('stripe-only')) {
            $this->comment('Skipped local webhook processing (--stripe-only). Ensure stripe listen is forwarding webhooks.');

            return self::SUCCESS;
        }

        $this->processSucceededWebhook($webhooks, $purchase, $intent);

        $purchase->refresh();

        $this->newLine();
        $this->table(
            ['Field', 'Value'],
            [
                ['status', $purchase->status->value],
                ['fulfilled_at', $purchase->fulfilled_at?->toIso8601String() ?? 'null'],
                ['entitlement_grants', (string) EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->count()],
                ['unlocked_submissions', (string) DuaSubmission::query()->where('unlock_purchase_id', $purchase->id)->count()],
                ['fulfillment_events', (string) BillingPurchaseEvent::query()
                    ->where('billing_purchase_id', $purchase->id)
                    ->whereIn('event_type', ['fulfillment.started', 'fulfillment.applied'])
                    ->count()],
            ],
        );

        if ($purchase->fulfilled_at === null) {
            $this->warn('Purchase is succeeded but fulfilled_at is still null. Check product code and fulfillment logs.');

            return self::FAILURE;
        }

        $this->info('Purchase confirmed and fulfillment processed locally.');

        return self::SUCCESS;
    }

    private function intentForLocalProcessing(
        StripePaymentIntentService $paymentIntents,
        PaymentIntent $intent,
    ): PaymentIntent {
        if ($intent->status === 'succeeded') {
            $this->comment('Using succeeded Payment Intent from Stripe for local webhook processing.');

            return $intent;
        }

        if ($paymentIntents->usesRedirectPaymentMethods($intent)) {
            $this->warn('Simulating payment_intent.succeeded locally for a legacy Payment Intent.');
        } else {
            $this->comment('Simulating payment_intent.succeeded locally without Stripe confirmation.');
        }

        $payload = json_decode($intent->toJSON(), true, 512, JSON_THROW_ON_ERROR);
        $payload['status'] = 'succeeded';

        return PaymentIntent::constructFrom($payload);
    }

    private function resolvePurchase(): ?BillingPurchase
    {
        $purchaseId = $this->option('purchase');
        $paymentIntentId = $this->option('payment-intent');

        if ($purchaseId === null && $paymentIntentId === null) {
            $this->error('Provide --purchase=ID or --payment-intent=pi_...');

            return null;
        }

        if ($purchaseId !== null && $paymentIntentId !== null) {
            $this->error('Provide only one of --purchase or --payment-intent.');

            return null;
        }

        $purchase = $purchaseId !== null
            ? BillingPurchase::query()->find($purchaseId)
            : BillingPurchase::query()->where('payment_intent_id', $paymentIntentId)->first();

        if ($purchase === null) {
            $this->error('Billing purchase not found.');

            return null;
        }

        return $purchase;
    }

    private function confirmPaymentIntent(
        StripePaymentIntentService $paymentIntents,
        string $paymentIntentId,
        PaymentIntent $intent,
    ): PaymentIntent {
        if ($intent->status === 'succeeded') {
            $this->comment('Payment Intent is already succeeded in Stripe; skipping confirmation.');

            return $intent;
        }

        if ($intent->status === 'canceled') {
            throw new RuntimeException('Payment Intent is canceled and cannot be confirmed.');
        }

        return $paymentIntents->confirmInTestMode($paymentIntentId);
    }

    private function processSucceededWebhook(
        BillingPurchaseWebhookService $webhooks,
        BillingPurchase $purchase,
        PaymentIntent $intent,
    ): void {
        if ($purchase->status === BillingPurchaseStatus::Succeeded && $purchase->fulfilled_at !== null) {
            $this->comment('Purchase is already succeeded and fulfilled; webhook processing skipped.');

            return;
        }

        $event = Event::constructFrom([
            'id' => 'evt_dev_confirm_'.$purchase->id.'_'.now()->timestamp,
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => json_decode($intent->toJSON(), true, 512, JSON_THROW_ON_ERROR),
            ],
        ]);

        $webhooks->handle($event);

        $this->info('Processed payment_intent.succeeded webhook locally.');
    }
}
