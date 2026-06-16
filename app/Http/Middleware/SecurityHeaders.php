<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    private const STRIPE_JS = 'https://js.stripe.com';

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->headers->has('Content-Type') && ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $styleSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://fonts.bunny.net',
            self::STRIPE_JS,
        ];
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            "'unsafe-eval'",
            self::STRIPE_JS,
        ];
        $connectSrc = [
            "'self'",
            'https://api.stripe.com',
            'https://r.stripe.com',
        ];
        $imgSrc = [
            "'self'",
            'data:',
            'blob:',
            'https://*.stripe.com',
        ];

        if (app()->environment('local')) {
            $viteOrigin = 'http://127.0.0.1:5173';
            $viteWebSocket = 'ws://127.0.0.1:5173';

            $styleSrc[] = $viteOrigin;
            $scriptSrc[] = $viteOrigin;
            $connectSrc[] = $viteOrigin;
            $connectSrc[] = $viteWebSocket;
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self' https://checkout.stripe.com",
            'img-src '.implode(' ', $imgSrc),
            "font-src 'self' https://fonts.bunny.net",
            'style-src '.implode(' ', $styleSrc),
            'script-src '.implode(' ', $scriptSrc),
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.self::STRIPE_JS,
        ]);
    }
}
