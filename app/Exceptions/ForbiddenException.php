<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(
        string $message = 'This action is unauthorized.',
        ?string $errorCode = 'forbidden',
        array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $errorCode, $errors, $previous);
    }
}
