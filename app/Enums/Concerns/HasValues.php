<?php

namespace App\Enums\Concerns;

trait HasValues
{
    /**
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function contains(string|int $value): bool
    {
        return in_array($value, self::values(), true);
    }
}
