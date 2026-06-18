<?php

namespace App\Exceptions;

use RuntimeException;

class AdminExportRateLimitException extends RuntimeException
{
    public static function exceeded(): self
    {
        return new self('Export rate limit exceeded. Please try again later.');
    }
}
