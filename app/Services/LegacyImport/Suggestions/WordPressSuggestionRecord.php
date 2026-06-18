<?php

namespace App\Services\LegacyImport\Suggestions;

readonly class WordPressSuggestionRecord
{
    public function __construct(
        public int $wpPostId,
        public string $postType,
        public string $title,
        public string $content,
        public string $category,
        public string $sourceType,
        public ?string $sourceReference,
        public int $usedCount,
        public bool $isVisible,
    ) {}

    /**
     * @return array{wp_post_id: int, title: string, source_type: string}
     */
    public function summary(): array
    {
        return [
            'wp_post_id' => $this->wpPostId,
            'title' => $this->title,
            'source_type' => $this->sourceType,
        ];
    }
}
