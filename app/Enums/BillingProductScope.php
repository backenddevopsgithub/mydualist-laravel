<?php

namespace App\Enums;

enum BillingProductScope: string
{
    case User = 'user';
    case List = 'list';
    case CommunityDua = 'community_dua';
}
