<?php

namespace App\Services\Blog;

use Carbon\CarbonInterface;

readonly class WordPressPostRecord
{
    public function __construct(
        public int $wpPostId,
        public string $title,
        public string $slug,
        public string $content,
        public ?string $excerpt = null,
        public ?CarbonInterface $publishedAt = null,
        public ?string $featuredImageUrl = null,
        public ?string $categorySlug = null,
        public ?string $categoryName = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?string $ogImageUrl = null,
    ) {}
}
