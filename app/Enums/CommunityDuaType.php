<?php

namespace App\Enums;

enum CommunityDuaType: string
{
    case Free = 'free';
    case Paid = 'paid';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function requiredCompletions(): int
    {
        return match ($this) {
            self::Free => 1,
            self::Paid => 20,
        };
    }
}
