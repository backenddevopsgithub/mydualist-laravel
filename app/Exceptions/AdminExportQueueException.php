<?php

namespace App\Exceptions;

use RuntimeException;

class AdminExportQueueException extends RuntimeException
{
    public static function syncConnectionNotAllowedInProduction(): self
    {
        return new self('Admin exports require an async queue connection in production.');
    }
}
