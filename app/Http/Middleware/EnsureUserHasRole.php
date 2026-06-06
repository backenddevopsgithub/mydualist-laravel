<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null || ! in_array($user->role->value, $roles, true)) {
            throw new ForbiddenException(
                message: 'You do not have permission to perform this action.',
                errorCode: 'insufficient_role',
            );
        }

        return $next($request);
    }
}
