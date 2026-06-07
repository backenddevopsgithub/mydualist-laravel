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
            'safar-travel' => 'Safar / Travel',
            'wedding' => 'Wedding',
            'aqiqah' => 'Aqiqah',
            'tahajjud' => 'Tahajjud',
            'quran-khatam' => 'Quran Khatam',
            'other' => 'Other',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }
}
