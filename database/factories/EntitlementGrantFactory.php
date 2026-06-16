<?php

namespace Database\Factories;

use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\EntitlementGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntitlementGrant>
 */
class EntitlementGrantFactory extends Factory
{
    protected $model = EntitlementGrant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'dua_list_id' => null,
            'entitlement_key' => EntitlementKey::UserUnlimitedForever,
            'quantity' => 1,
            'is_stackable' => false,
            'dedupe_key' => null,
            'source_purchase_id' => null,
            'granted_at' => now(),
            'expires_at' => null,
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EntitlementGrant $grant): void {
            if ($grant->dedupe_key !== null || $grant->is_stackable) {
                return;
            }

            if ($grant->dua_list_id !== null) {
                $grant->dedupe_key = EntitlementGrant::dedupeKeyForListGrant(
                    (int) $grant->dua_list_id,
                    $grant->entitlement_key,
                );

                return;
            }

            $grant->dedupe_key = EntitlementGrant::dedupeKeyForUserGrant(
                (int) $grant->user_id,
                $grant->entitlement_key,
            );
        });
    }

    public function fromPurchase(BillingPurchase $purchase): static
    {
        return $this->state(fn (): array => [
            'user_id' => $purchase->user_id,
            'source_purchase_id' => $purchase->id,
        ]);
    }
}
