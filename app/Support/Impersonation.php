<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpFoundation\Response;

class Impersonation
{
    public static function manager(): ImpersonateManager
    {
        return app(ImpersonateManager::class);
    }

    public static function isActive(): bool
    {
        return static::manager()->isImpersonating();
    }

    public static function impersonator(): ?User
    {
        $impersonator = static::manager()->getImpersonator();

        return $impersonator instanceof User ? $impersonator : null;
    }

    public static function ensureSensitiveActionAllowed(): void
    {
        if (! static::isActive()) {
            return;
        }

        throw new HttpResponseException(
            response('This action is not allowed while impersonating a user.', Response::HTTP_FORBIDDEN),
        );
    }
}
