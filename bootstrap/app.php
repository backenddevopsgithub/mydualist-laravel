<?php

/*
| Laravel merges framework config from vendor on boot. Until Laravel 11.54's
| vendor database.php is updated for PHP 8.5, suppress display of that specific
| deprecation so it cannot corrupt HTML responses and break Livewire/Filament JS.
*/
if (PHP_VERSION_ID >= 80500) {
    set_error_handler(static function (int $severity, string $message): bool {
        return $severity === E_DEPRECATED
            && str_contains($message, 'PDO::MYSQL_ATTR_SSL_CA');
    }, E_DEPRECATED);
}

use App\Exceptions\ApiException;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(replace: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class => VerifyCsrfToken::class,
        ]);

        // Sanctum stateful middleware applies to API routes only — not Filament/web.
        $middleware->statefulApi();

        $middleware->alias([
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ApiException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $exception->render($request);
            }
        });
    })->create();
