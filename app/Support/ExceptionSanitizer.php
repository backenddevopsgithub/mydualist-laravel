<?php

namespace App\Support;

use Illuminate\Support\Str;
use Throwable;

class ExceptionSanitizer
{
    public static function forUser(?Throwable $exception): string
    {
        if ($exception === null) {
            return 'An unexpected error occurred. Please try again later.';
        }

        if (app()->hasDebugModeEnabled()) {
            return Str::limit($exception->getMessage(), 500);
        }

        return 'An unexpected error occurred. Please try again later.';
    }

    public static function forStorage(?Throwable $exception): string
    {
        if ($exception === null) {
            return 'An unexpected error occurred.';
        }

        if (app()->hasDebugModeEnabled()) {
            return Str::limit($exception->getMessage(), 1000);
        }

        return 'An unexpected error occurred.';
    }

    public static function forDisplay(?string $exception): string
    {
        if ($exception === null || $exception === '') {
            return 'No exception details available.';
        }

        if (app()->hasDebugModeEnabled()) {
            return $exception;
        }

        $firstLine = Str::before($exception, "\n");

        return Str::limit($firstLine, 200);
    }
}
