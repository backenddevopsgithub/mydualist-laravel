<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Enums\CommunityDuaStatus;
use App\Models\CommunityDua;
use App\Models\StripePayment;
use Illuminate\Support\Arr;

/**
 * @deprecated Legacy Stripe Checkout Session fulfillment. Use {@see CommunityDuaPaidFulfillmentHandler}
 *             until rollout monitoring is complete.
 */
class FulfillPaidCommunityDuaCheckoutAction extends Action
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
    ) {}

    public function handle(mixed ...$args): mixed
    {
        $session = $args[0];
        $eventId = $args[1] ?? null;

        $sessionId = (string) data_get($session, 'id');
        $paymentStatus = (string) data_get($session, 'payment_status', data_get($session, 'status'));

        if ($paymentStatus !== 'paid' && data_get($session, 'status') !== 'complete') {
            return null;
        }

        $metadata = $this->metadata($session);
        $communityDuaId = (int) ($metadata['community_dua_id'] ?? 0);

        if ($communityDuaId === 0) {
            return null;
        }

        /** @var CommunityDua $communityDua */
        $communityDua = CommunityDua::query()->findOrFail($communityDuaId);

        if ($communityDua->status !== CommunityDuaStatus::PendingPayment) {
            return $communityDua;
        }

        $payment = StripePayment::query()->updateOrCreate(
            ['stripe_checkout_session_id' => $sessionId],
            [
                'user_id' => ! empty($metadata['user_id']) ? (int) $metadata['user_id'] : null,
                'stripe_payment_intent_id' => data_get($session, 'payment_intent'),
                'stripe_event_id' => $eventId,
                'amount_total' => data_get($session, 'amount_total'),
                'currency' => data_get($session, 'currency'),
                'status' => StripePayment::STATUS_PAID,
                'metadata' => $metadata,
                'paid_at' => now(),
            ],
        );

        $communityDua->forceFill([
            'status' => CommunityDuaStatus::Active,
            'is_visible' => true,
            'stripe_payment_id' => $payment->id,
        ])->save();

        $this->queue->notifyWaitingUsersOfNewDua($communityDua->fresh());

        return $communityDua->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(mixed $session): array
    {
        $metadata = data_get($session, 'metadata', []);

        if ($metadata instanceof \Stripe\StripeObject) {
            return $metadata->toArray();
        }

        return Arr::wrap($metadata);
    }
}
