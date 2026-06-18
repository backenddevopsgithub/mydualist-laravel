<?php

namespace App\Domains\Billing\Services;

use App\Enums\EntitlementKey;
use App\Enums\EntitlementProductType;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\Service;
use App\Support\Impersonation;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use RuntimeException;

class EntitlementGrantManagementService extends Service
{
    public function __construct(
        private readonly EntitlementGrantService $entitlementGrants,
    ) {}

    public function createGrant(
        User $user,
        EntitlementProductType $product,
        User $grantedBy,
        ?int $duaListId = null,
        ?CarbonInterface $expiresAt = null,
    ): EntitlementGrant {
        Impersonation::ensureSensitiveActionAllowed();

        $this->assertGrantable($user, $product, $duaListId);

        $attributes = [
            'entitlement_key' => $product->entitlementKey(),
            'quantity' => $product->defaultQuantity(),
            'is_stackable' => $product->isStackable(),
            'granted_at' => now(),
            'expires_at' => $expiresAt,
            'metadata' => $this->adminMetadata($grantedBy, 'create'),
        ];

        if ($product->isStackable()) {
            return EntitlementGrant::query()->create(array_merge($attributes, [
                'user_id' => $user->id,
                'dua_list_id' => $duaListId,
                'dedupe_key' => 'admin:grant:'.Str::uuid(),
            ]));
        }

        $this->assertNoActiveUniqueGrant($user, $product->entitlementKey(), $duaListId);

        return EntitlementGrant::query()->create(array_merge($attributes, [
            'user_id' => $user->id,
            'dua_list_id' => $duaListId,
            'dedupe_key' => $this->dedupeKeyFor($user, $product->entitlementKey(), $duaListId),
        ]));
    }

    public function revokeGrant(EntitlementGrant $grant, User $revokedBy): EntitlementGrant
    {
        if (! $grant->isActive()) {
            throw new RuntimeException('Grant is already inactive.');
        }

        $grant->forceFill([
            'expires_at' => now(),
            'metadata' => array_merge($grant->metadata ?? [], [
                'revoked_by' => $revokedBy->id,
                'revoked_at' => now()->toIso8601String(),
            ]),
        ])->save();

        return $grant->fresh();
    }

    public function extendExpiration(
        EntitlementGrant $grant,
        CarbonInterface $expiresAt,
        User $extendedBy,
    ): EntitlementGrant {
        if ($expiresAt->isPast()) {
            throw new RuntimeException('Expiration must be in the future.');
        }

        $grant->forceFill([
            'expires_at' => $expiresAt,
            'metadata' => array_merge($grant->metadata ?? [], [
                'expiration_extended_by' => $extendedBy->id,
                'expiration_extended_at' => now()->toIso8601String(),
            ]),
        ])->save();

        return $grant->fresh();
    }

    public function assertGrantable(
        User $user,
        EntitlementProductType $product,
        ?int $duaListId = null,
    ): void {
        $this->validateProductTarget($user, $product, $duaListId);

        if (! $product->isStackable()) {
            $this->assertNoActiveUniqueGrant($user, $product->entitlementKey(), $duaListId);
        }
    }

    private function validateProductTarget(
        User $user,
        EntitlementProductType $product,
        ?int $duaListId,
    ): void {
        if ($product->requiresList() && $duaListId === null) {
            throw new RuntimeException('This product requires a dua list.');
        }

        if (! $product->requiresList() && $duaListId !== null) {
            throw new RuntimeException('This product cannot be scoped to a dua list.');
        }

        if ($duaListId !== null) {
            $list = DuaList::query()->find($duaListId);

            if ($list === null || $list->user_id !== $user->id) {
                throw new RuntimeException('The selected dua list does not belong to this user.');
            }
        }
    }

    private function assertNoActiveUniqueGrant(
        User $user,
        EntitlementKey $key,
        ?int $duaListId,
    ): void {
        if ($this->entitlementGrants->hasEntitlement($user, $key, $duaListId)) {
            throw new RuntimeException('An active grant already exists for this entitlement.');
        }
    }

    private function dedupeKeyFor(User $user, EntitlementKey $key, ?int $duaListId): string
    {
        if ($duaListId !== null) {
            return EntitlementGrant::dedupeKeyForListGrant($duaListId, $key);
        }

        return EntitlementGrant::dedupeKeyForUserGrant($user->id, $key);
    }

    /**
     * @return array<string, mixed>
     */
    private function adminMetadata(User $grantedBy, string $action): array
    {
        return [
            'source' => 'admin',
            'admin_action' => $action,
            'granted_by' => $grantedBy->id,
            'granted_by_email' => $grantedBy->email,
        ];
    }
}
