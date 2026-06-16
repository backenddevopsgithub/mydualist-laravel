<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * @param  array<int, string|null>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        if (empty($guards)) {
            $guards = [null];
        }

        if ($this->allowsGuestSanctum($request, $guards)) {
            $this->auth->shouldUse('sanctum');

            try {
                parent::authenticate($request, $guards);
            } catch (AuthenticationException) {
                // Guest access is permitted on billing purchase routes without a bearer token.
            }

            return;
        }

        parent::authenticate($request, $guards);
    }

    /**
     * @param  array<int, string|null>  $guards
     */
    protected function allowsGuestSanctum(Request $request, array $guards): bool
    {
        if (! $request->routeIs(
            'api.v1.billing.purchases.store',
            'api.v1.billing.purchases.show',
            'api.v1.billing.purchases.client-secret',
            'api.v1.billing.purchases.payment-status',
        )) {
            return false;
        }

        if ($request->bearerToken() !== null) {
            return false;
        }

        return in_array('sanctum', $guards, true);
    }
}
