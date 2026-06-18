<?php

namespace App\Services\LegacyImport\CommunityDuas;

readonly class WordPressCommunityQueueRecord
{
    public function __construct(
        public int $userWpLegacyId,
        public string $showingType,
        public int $pattern,
        public ?int $seeingNowWpPostId,
        /** @var list<int> */
        public array $completedWpPostIds,
        /** @var list<int> */
        public array $seenWpPostIds,
    ) {}
}
