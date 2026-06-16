<?php

namespace App\Domains\Billing\Exceptions;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException {}
