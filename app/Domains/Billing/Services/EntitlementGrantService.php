<?php

namespace App\Domains\Billing\Services;

use App\Enums\EntitlementKey;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\Service;
use App\Support\MemoizesPerRequest;
use Illuminate\Database\Eloquent\Builder;

class EntitlementGrantService extends Service
{
    use MemoizesPerRequest;

    public function hasEntitlement(User $user, EntitlementKey|string $key, ?int $duaListId = null): bool
    {
        return $this->quantity($user, $key, $duaListId) > 0;
    }

    public function quantity(User $user, EntitlementKey|string $key, ?int $duaListId = null): int
    {
        if ($duaListId !== null) {
            return $this->listScopedQuantity($user, $key, $duaListId);
        }

        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        return $this->memo(
            "quantity:{$user->id}:{$keyValue}:global",
            fn (): int => (int) $this->activeGrantsQuery($user)
                ->where('entitlement_key', $keyValue)
                ->whereNull('dua_list_id')
                ->sum('quantity'),
        );
    }

    public function listScopedQuantity(User $user, EntitlementKey|string $key, int $duaListId): int
    {
        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        return $this->memo(
            "listScopedQuantity:{$duaListId}:{$keyValue}",
            fn (): int => (int) $this->activeGrantsForListQuery($duaListId)
                ->where('entitlement_key', $keyValue)
                ->sum('quantity'),
        );
    }

    /**
     * @return Builder<EntitlementGrant>
     */
    private function activeGrantsForListQuery(int $duaListId): Builder
    {
        return EntitlementGrant::query()
            ->where('dua_list_id', $duaListId)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * @return Builder<EntitlementGrant>
     */
    private function activeGrantsQuery(User $user): Builder
    {
        return EntitlementGrant::query()
            ->where('user_id', $user->id)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
