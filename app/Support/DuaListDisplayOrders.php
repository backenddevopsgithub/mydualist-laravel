<?php

namespace App\Support;

class DuaListDisplayOrders
{
    public const DATE = 'date';

    public const GENDER = 'gender';

    public const PERSON = 'person';

    public const DEFAULT = self::DATE;

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::DATE,
            self::GENDER,
            self::PERSON,
        ];
    }
}
