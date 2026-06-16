<?php

namespace App\Domains\Billing\Data;

final readonly class EntitlementSnapshot
{
    public function __construct(
        public int $effectiveListCapacity,
        public bool $hasUnlimitedListCapacity,
        public int $extraListSlots,
        public bool $hasUnlimitedForever,
        public bool $hasLegacyPremium,
        public int $activeListCount,
        public int $remainingListSlots,
        public bool $canCreateList,
    ) {}
}
