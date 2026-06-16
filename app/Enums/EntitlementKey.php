<?php

namespace App\Enums;

enum EntitlementKey: string
{
    case UserExtraListSlot = 'user.extra_list_slot';
    case UserUnlimitedForever = 'user.unlimited_forever';
    case ListVisibleSubmissionPack = 'list.visible_submission_pack';
    case ListUnlimitedOverride = 'list.unlimited_override';
}
