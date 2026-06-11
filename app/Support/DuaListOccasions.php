<?php

namespace App\Support;

class DuaListOccasions
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'umrah' => 'Umrah',
            'hajj' => 'Hajj',
            'ramadan' => 'Ramadan',
            'salawat' => 'Salawat',
            'rawadah' => 'Rawadah',
            'general' => 'General',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $occasion): string
    {
        return self::labels()[$occasion] ?? str($occasion)->headline()->replace('-', ' ')->toString();
    }
}
