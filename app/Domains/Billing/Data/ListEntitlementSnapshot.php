<?php

namespace App\Domains\Billing\Data;

final readonly class ListEntitlementSnapshot
{
    public function __construct(
        public int $effectiveVisibleQuota,
        public bool $hasListUnlimitedOverride,
        public bool $hasUnlimitedForever,
        public int $bonusVisibleSubmissions,
        public int $lockedSubmissionCount,
    ) {}
}
