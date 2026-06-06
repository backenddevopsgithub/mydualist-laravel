<?php

namespace App\Exceptions;

class DomainException extends ApiException
{
    public function __construct(
        string $message,
        ?string $errorCode = null,
        array $errors = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 422, $errorCode, $errors, $previous);
    }
}
