<?php

namespace App\Support;

trait MemoizesPerRequest
{
    /** @var array<string, mixed> */
    private array $requestMemo = [];

    private function memo(string $key, callable $callback): mixed
    {
        if (! array_key_exists($key, $this->requestMemo)) {
            $this->requestMemo[$key] = $callback();
        }

        return $this->requestMemo[$key];
    }
}
