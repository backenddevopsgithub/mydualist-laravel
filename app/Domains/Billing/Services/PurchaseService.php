<?php

namespace App\Domains\Billing\Services;

use App\Domains\Billing\Exceptions\IdempotencyConflictException;
use App\Enums\BillingProductScope;
use App\Enums\BillingPurchaseStatus;
use App\Models\BillingProduct;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\User;
use App\Services\Service;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseService extends Service
{
    public function __construct(
        private readonly PurchaseAccessService $access,
    ) {}

    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function findAccessible(BillingPurchase $purchase, ?User $user): BillingPurchase
    {
        $this->access->assertAccessible($purchase, $user);

        return $purchase->loadMissing('product', 'duaList');
    }

    /**
     * @return array{client_secret: string|null, payment_intent_id: string|null}
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     * @throws RuntimeException
     */
    public function clientSecretFor(BillingPurchase $purchase, ?User $user, ?StripePaymentIntentService $paymentIntents = null): array
    {
        $purchase = $this->findAccessible($purchase, $user);

        if (! $purchase->isPayable()) {
            throw new RuntimeException('This purchase is no longer payable.');
        }

        if (! $purchase->payment_intent_id) {
            throw new RuntimeException('This purchase does not have a payment intent.');
        }

        $paymentIntents ??= app(StripePaymentIntentService::class);
        $intent = $paymentIntents->retrieve($purchase->payment_intent_id);

        return [
            'client_secret' => $intent['client_secret'],
            'payment_intent_id' => $intent['id'],
        ];
    }

    /**
     * @return array{
     *     status: string,
     *     is_payable: bool,
     *     is_completed: bool,
     *     fulfilled_at: string|null,
     *     failure_reason: string|null
     * }
     *
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function paymentStatusFor(BillingPurchase $purchase, ?User $user): array
    {
        $purchase = $this->findAccessible($purchase, $user);

        return [
            'status' => $purchase->status->value,
            'is_payable' => $purchase->isPayable(),
            'is_completed' => $purchase->isCompleted(),
            'fulfilled_at' => $purchase->fulfilled_at?->toIso8601String(),
            'failure_reason' => $purchase->failure_reason,
        ];
    }

    /**
     * @param  array{product_code: string, idempotency_key: string, dua_list_id?: int|null, community_dua_id?: int|null, metadata?: array<string, mixed>}  $payload
     * @return array{purchase: BillingPurchase, created: bool, client_secret: string|null}
     */
    public function create(array $payload, ?User $user, ?StripePaymentIntentService $paymentIntents = null): array
    {
        $paymentIntents ??= app(StripePaymentIntentService::class);

        $product = BillingProduct::query()
            ->where('code', $payload['product_code'])
            ->where('active', true)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_code' => ['The selected product is not active.'],
            ]);
        }

        if ($product->requiresAuthentication() && $user === null) {
            throw new AuthenticationException('Authentication is required for this product.');
        }

        [$resolvedUserId, $duaListId, $communityDuaId] = $this->resolveScopeTargets($product, $payload, $user);

        $existing = BillingPurchase::query()
            ->with('product')
            ->where('idempotency_key', $payload['idempotency_key'])
            ->first();

        if ($existing) {
            $matches = $existing->billing_product_id === $product->id
                && $existing->user_id === $resolvedUserId
                && $existing->dua_list_id === $duaListId
                && $existing->community_dua_id === $communityDuaId;

            if (! $matches) {
                throw new IdempotencyConflictException('This idempotency key was already used with different parameters.');
            }

            $clientSecret = null;

            if ($existing->payment_intent_id) {
                $clientSecret = $paymentIntents->retrieve($existing->payment_intent_id)['client_secret'];
            }

            return ['purchase' => $existing, 'created' => false, 'client_secret' => $clientSecret];
        }

        $purchase = BillingPurchase::query()->create([
            'billing_product_id' => $product->id,
            'user_id' => $resolvedUserId,
            'dua_list_id' => $duaListId,
            'community_dua_id' => $communityDuaId,
            'status' => BillingPurchaseStatus::RequiresPaymentMethod,
            'amount_minor' => $product->amount_minor,
            'currency' => $product->currency ?? (string) config('billing.currency', 'gbp'),
            'idempotency_key' => $payload['idempotency_key'],
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $intent = $paymentIntents->createForPurchase($purchase->load('product'));

        $purchase->forceFill([
            'payment_intent_id' => $intent['id'],
        ])->save();

        return [
            'purchase' => $purchase->fresh(['product']),
            'created' => true,
            'client_secret' => $intent['client_secret'],
        ];
    }

    /**
     * @param  array{dua_list_id?: int|null, community_dua_id?: int|null}  $payload
     * @return array{0: int|null, 1: int|null, 2: int|null}
     */
    private function resolveScopeTargets(BillingProduct $product, array $payload, ?User $user): array
    {
        $duaListId = $payload['dua_list_id'] ?? null;
        $communityDuaId = $payload['community_dua_id'] ?? null;

        if ($product->scope === BillingProductScope::User) {
            if ($duaListId !== null || $communityDuaId !== null) {
                throw ValidationException::withMessages([
                    'product_code' => ['This product does not accept list or community scope targets.'],
                ]);
            }

            return [$user?->id, null, null];
        }

        if ($product->scope === BillingProductScope::List) {
            if ($duaListId === null) {
                throw ValidationException::withMessages([
                    'dua_list_id' => ['A list target is required for this product.'],
                ]);
            }

            $list = DuaList::query()->find($duaListId);

            if (! $list) {
                throw ValidationException::withMessages([
                    'dua_list_id' => ['The selected list was not found.'],
                ]);
            }

            if ($user === null || $list->user_id !== $user->id) {
                throw ValidationException::withMessages([
                    'dua_list_id' => ['You can only purchase list products for your own lists.'],
                ]);
            }

            if ($communityDuaId !== null) {
                throw ValidationException::withMessages([
                    'community_dua_id' => ['List products cannot target a community dua.'],
                ]);
            }

            return [$user->id, $list->id, null];
        }

        if ($communityDuaId === null) {
            throw ValidationException::withMessages([
                'community_dua_id' => ['A community dua target is required for this product.'],
            ]);
        }

        $communityDua = CommunityDua::query()->find($communityDuaId);

        if (! $communityDua) {
            throw ValidationException::withMessages([
                'community_dua_id' => ['The selected community dua was not found.'],
            ]);
        }

        if ($duaListId !== null) {
            throw ValidationException::withMessages([
                'dua_list_id' => ['Community dua products cannot target a list.'],
            ]);
        }

        return [$user?->id, null, $communityDua->id];
    }
}
