<?php

namespace App\Exceptions;

use RuntimeException;

class AdminExportDuplicateException extends RuntimeException
{
    public static function pendingExportExists(): self
    {
        return new self('An export is already pending or processing for this account.');
    }
}
