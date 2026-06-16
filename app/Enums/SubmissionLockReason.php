<?php

namespace App\Enums;

enum SubmissionLockReason: string
{
    case VisibleQuotaExhausted = 'visible_quota_exhausted';
}
