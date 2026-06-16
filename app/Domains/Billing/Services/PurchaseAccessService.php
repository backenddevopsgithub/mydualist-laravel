<?php

namespace App\Domains\Billing\Services;

use App\Enums\BillingProductCode;
use App\Enums\BillingProductScope;
use App\Models\BillingPurchase;
use App\Models\User;
use App\Services\Service;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

class PurchaseAccessService extends Service
{
    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    public function assertAccessible(BillingPurchase $purchase, ?User $user): void
    {
        $purchase->loadMissing('product', 'duaList');

        $product = $purchase->product;

        if ($product === null) {
            throw new AuthorizationException('This purchase is not available.');
        }

        if ($product->requiresAuthentication()) {
            $this->assertAuthenticatedProductAccess($purchase, $user);

            return;
        }

        $this->assertGuestAllowedProductAccess($purchase, $user);
    }

    /**
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    private function assertAuthenticatedProductAccess(BillingPurchase $purchase, ?User $user): void
    {
        if ($user === null) {
            throw new AuthenticationException('Authentication is required for this purchase.');
        }

        if ($purchase->product?->scope === BillingProductScope::List) {
            if ($purchase->duaList?->user_id !== $user->id) {
                throw new AuthorizationException('You do not have access to this purchase.');
            }

            return;
        }

        if ($purchase->user_id !== $user->id) {
            throw new AuthorizationException('You do not have access to this purchase.');
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function assertGuestAllowedProductAccess(BillingPurchase $purchase, ?User $user): void
    {
        if ($purchase->product?->code !== BillingProductCode::CommunityDuaPaid->value) {
            throw new AuthorizationException('Guest access is not allowed for this purchase.');
        }

        if ($user !== null && $purchase->user_id !== null && $purchase->user_id !== $user->id) {
            throw new AuthorizationException('You do not have access to this purchase.');
        }
    }
}
