<?php

namespace App\Policies;

use App\Models\BillingPurchase;
use App\Models\User;
use App\Support\Impersonation;

class BillingPurchasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, BillingPurchase $purchase): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BillingPurchase $purchase): bool
    {
        return $user->isAdmin() && ! Impersonation::isActive();
    }
}
