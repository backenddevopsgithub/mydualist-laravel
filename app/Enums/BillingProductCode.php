<?php

namespace App\Enums;

enum BillingProductCode: string
{
    case RequestPack25 = 'REQUEST_PACK_25';
    case UnlimitedOneList = 'UNLIMITED_ONE_LIST';
    case AdditionalList = 'ADDITIONAL_LIST';
    case UnlimitedForever = 'UNLIMITED_FOREVER';
    case CommunityDuaPaid = 'COMMUNITY_DUA_PAID';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
