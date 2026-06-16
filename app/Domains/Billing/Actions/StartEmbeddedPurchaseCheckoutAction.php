<?php

namespace App\Domains\Billing\Actions;

use App\Actions\Action;
use App\Domains\Billing\Services\PurchaseService;
use App\Domains\Billing\Support\PurchaseCheckoutRedirectResolver;
use App\Models\BillingPurchase;
use App\Models\User;
use Illuminate\Support\Str;

class StartEmbeddedPurchaseCheckoutAction extends Action
{
    public function __construct(
        private readonly PurchaseService $purchases,
        private readonly PurchaseCheckoutRedirectResolver $redirects,
    ) {}

    /**
     * @param  array{
     *     product_code: string,
     *     dua_list_id?: int|null,
     *     community_dua_id?: int|null,
     *     metadata?: array<string, mixed>
     * }  $payload
     * @return array{purchase: BillingPurchase}
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var User $user */
        $user = $args[0];
        $payload = $args[1];

        $metadata = array_merge($payload['metadata'] ?? [], [
            'checkout_source' => 'embedded',
        ]);

        $result = $this->purchases->create([
            'product_code' => $payload['product_code'],
            'idempotency_key' => (string) Str::uuid(),
            'dua_list_id' => $payload['dua_list_id'] ?? null,
            'community_dua_id' => $payload['community_dua_id'] ?? null,
            'metadata' => $metadata,
        ], $user);

        /** @var BillingPurchase $purchase */
        $purchase = $result['purchase']->fresh(['product']);

        $purchase->forceFill([
            'metadata' => array_merge($purchase->metadata ?? [], [
                'success_url' => $this->redirects->successUrl($purchase),
                'failure_url' => $this->redirects->failureUrl($purchase),
            ]),
        ])->save();

        return ['purchase' => $purchase->fresh(['product'])];
    }
}
