<?php

namespace App\Enums;

enum DuaSubmissionStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Hidden = 'hidden';
    case Archived = 'archived';
    case Reported = 'reported';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
