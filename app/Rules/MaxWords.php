<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class MaxWords implements ValidationRule
{
    public function __construct(
        private readonly int $maxWords,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be text.');

            return;
        }

        $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) > $this->maxWords) {
            $fail("The :attribute must not exceed {$this->maxWords} words.");
        }
    }
}
