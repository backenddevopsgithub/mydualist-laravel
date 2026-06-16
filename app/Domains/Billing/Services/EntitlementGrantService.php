<?php

namespace App\Domains\Billing\Services;

use App\Enums\EntitlementKey;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Builder;

class EntitlementGrantService extends Service
{
    public function hasEntitlement(User $user, EntitlementKey|string $key, ?int $duaListId = null): bool
    {
        return $this->quantity($user, $key, $duaListId) > 0;
    }

    public function quantity(User $user, EntitlementKey|string $key, ?int $duaListId = null): int
    {
        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        $query = $this->activeGrantsQuery($user)
            ->where('entitlement_key', $keyValue);

        if ($duaListId !== null) {
            $query->where('dua_list_id', $duaListId);
        } else {
            $query->whereNull('dua_list_id');
        }

        return (int) $query->sum('quantity');
    }

    public function listScopedQuantity(User $user, EntitlementKey|string $key, int $duaListId): int
    {
        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        return (int) $this->activeGrantsQuery($user)
            ->where('entitlement_key', $keyValue)
            ->where('dua_list_id', $duaListId)
            ->sum('quantity');
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
