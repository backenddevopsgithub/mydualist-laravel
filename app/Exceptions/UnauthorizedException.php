<?php

namespace App\Exceptions;

class UnauthorizedException extends ApiException
{
    public function __construct(
        string $message = 'Invalid credentials.',
        ?string $errorCode = 'invalid_credentials',
        array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $errorCode, $errors, $previous);
    }
}
