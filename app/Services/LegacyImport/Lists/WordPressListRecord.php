<?php

namespace App\Services\LegacyImport\Lists;

use Carbon\Carbon;

readonly class WordPressListRecord
{
    /**
     * @param  array{dua_limit_per_person: ?int, display_order: string, email_frequency: string}  $ownerPreferences
     */
    public function __construct(
        public int $wpPostId,
        public int $ownerWpLegacyId,
        public string $title,
        public string $slug,
        public string $occasion,
        public ?Carbon $startDate,
        public ?Carbon $endDate,
        public ?string $coverImageUrl,
        public string $status,
        public ?Carbon $publishedAt,
        public bool $isTrashed,
        public array $ownerPreferences,
        public ?Carbon $createdAt,
        public ?Carbon $updatedAt,
    ) {}

    /**
     * @return array{wp_post_id: int, slug: string, title: string}
     */
    public function summary(): array
    {
        return [
            'wp_post_id' => $this->wpPostId,
            'slug' => $this->slug,
            'title' => $this->title,
        ];
    }
}
