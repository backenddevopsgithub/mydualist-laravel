<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            ! $request->user()
            || ($request->user() instanceof MustVerifyEmail
                && ! $request->user()->hasVerifiedEmail())
        ) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return redirect()
                    ->route('onboarding.start')
                    ->withErrors(['email' => 'Please verify your email before accessing your dashboard.']);
            }

            return response()->json([
                'message' => 'Your email address is not verified.',
                'error_code' => 'email_not_verified',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
