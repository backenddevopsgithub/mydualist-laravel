<?php

namespace App\Actions;

abstract class Action
{
    abstract public function handle(mixed ...$args): mixed;

    public function __invoke(mixed ...$args): mixed
    {
        return $this->handle(...$args);
    }
}
