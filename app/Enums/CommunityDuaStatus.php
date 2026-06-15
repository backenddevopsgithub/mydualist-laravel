<?php

namespace App\Enums;

enum CommunityDuaStatus: string
{
    case PendingPayment = 'pending_payment';
    case Active = 'active';
    case Completed = 'completed';
    case Reported = 'reported';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
