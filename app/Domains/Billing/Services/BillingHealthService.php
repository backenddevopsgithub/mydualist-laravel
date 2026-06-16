<?php

namespace App\Domains\Billing\Services;

use App\Enums\BillingPurchaseEventType;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingPurchase;
use App\Models\BillingPurchaseEvent;
use App\Services\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BillingHealthService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'stripe_configured' => $this->stripeConfigured(),
            'stripe_live_mode' => $this->stripeLiveMode(),
            'unfulfilled_succeeded_purchases' => $this->unfulfilledSucceededCount(),
            'processing_purchases' => BillingPurchase::query()
                ->where('status', BillingPurchaseStatus::Processing)
                ->count(),
            'refunded_purchases' => BillingPurchase::query()->whereNotNull('refunded_at')->count(),
            'disputed_purchases' => BillingPurchase::query()->whereNotNull('disputed_at')->count(),
            'recent_webhook_failures' => BillingPurchaseEvent::query()
                ->where('event_type', BillingPurchaseEventType::WebhookFailure)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'legacy_checkout_deprecated' => true,
        ];
    }

    /**
     * @return list<string>
     */
    public function alerts(): array
    {
        $alerts = [];
        $threshold = (int) config('billing.monitoring.unfulfilled_purchase_alert_threshold', 5);

        if (! $this->stripeConfigured()) {
            $alerts[] = 'Stripe secret or webhook secret is not configured.';
        }

        if (app()->environment('production') && ! $this->stripeLiveMode()) {
            $alerts[] = 'Production environment is not using live Stripe keys.';
        }

        if ($this->unfulfilledSucceededCount() >= $threshold) {
            $alerts[] = "Unfulfilled succeeded purchases ({$this->unfulfilledSucceededCount()}) exceeded threshold ({$threshold}).";
        }

        $recentWebhookFailures = BillingPurchaseEvent::query()
            ->where('event_type', BillingPurchaseEventType::WebhookFailure)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($recentWebhookFailures > 0) {
            $alerts[] = "Billing webhook failures in the last 24h: {$recentWebhookFailures}.";
        }

        return $alerts;
    }

    public function notifyIfNeeded(): void
    {
        $alerts = $this->alerts();

        if ($alerts === []) {
            return;
        }

        foreach ($alerts as $alert) {
            Log::warning('billing.health.alert', ['message' => $alert]);
        }

        $webhook = config('billing.monitoring.alert_slack_webhook');

        if (! is_string($webhook) || $webhook === '') {
            return;
        }

        Http::post($webhook, [
            'text' => "MyDualist billing alerts:\n- ".implode("\n- ", $alerts),
        ]);
    }

    private function unfulfilledSucceededCount(): int
    {
        return BillingPurchase::query()
            ->where('status', BillingPurchaseStatus::Succeeded)
            ->whereNull('fulfilled_at')
            ->count();
    }

    private function stripeConfigured(): bool
    {
        return config('services.stripe.secret') && config('services.stripe.webhook_secret');
    }

    private function stripeLiveMode(): bool
    {
        $secret = (string) config('services.stripe.secret');

        return str_starts_with($secret, 'sk_live_');
    }
}
