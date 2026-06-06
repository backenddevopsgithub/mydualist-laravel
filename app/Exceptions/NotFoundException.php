<?php

namespace App\Exceptions;

class NotFoundException extends ApiException
{
    public function __construct(
        string $message = 'Resource not found.',
        ?string $errorCode = 'not_found',
        array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 404, $errorCode, $errors, $previous);
    }
}
