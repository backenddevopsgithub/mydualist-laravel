<?php

namespace App\Services\Blog\Import;

use App\Services\Blog\WordPressPostRecord;
use Carbon\Carbon;
use RuntimeException;

class CsvBlogImportSource implements BlogImportSource
{
    public function __construct(
        private readonly string $path,
    ) {}

    public function records(): iterable
    {
        if (! is_readable($this->path)) {
            throw new RuntimeException("CSV import file is not readable: {$this->path}");
        }

        $handle = fopen($this->path, 'r');

        if ($handle === false) {
            throw new RuntimeException("Unable to open CSV import file: {$this->path}");
        }

        try {
            $headers = fgetcsv($handle);

            if ($headers === false) {
                return;
            }

            $headers = array_map(fn (string $header): string => strtolower(trim($header)), $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                /** @var array<string, string|null> $data */
                $data = array_combine($headers, array_pad($row, count($headers), null));

                if ($data === false) {
                    continue;
                }

                $wpPostId = (int) ($data['wp_post_id'] ?? $data['id'] ?? 0);

                if ($wpPostId <= 0) {
                    continue;
                }

                $title = trim((string) ($data['post_title'] ?? $data['title'] ?? ''));
                $slug = trim((string) ($data['post_name'] ?? $data['slug'] ?? ''));
                $content = (string) ($data['post_content'] ?? $data['content'] ?? '');

                if ($title === '' || $slug === '' || $content === '') {
                    continue;
                }

                yield $wpPostId => new WordPressPostRecord(
                    wpPostId: $wpPostId,
                    title: $title,
                    slug: $slug,
                    content: $content,
                    excerpt: $this->nullableString($data['post_excerpt'] ?? $data['excerpt'] ?? null),
                    publishedAt: $this->parseDate($data['post_date'] ?? $data['published_at'] ?? null),
                    featuredImageUrl: $this->nullableString($data['featured_image_url'] ?? $data['featured_image'] ?? null),
                    categorySlug: $this->nullableString($data['category_slug'] ?? null),
                    categoryName: $this->nullableString($data['category_name'] ?? null),
                    metaTitle: $this->nullableString($data['meta_title'] ?? $data['seo_title'] ?? null),
                    metaDescription: $this->nullableString($data['meta_description'] ?? $data['seo_description'] ?? null),
                    ogImageUrl: $this->nullableString($data['og_image_url'] ?? null),
                );
            }
        } finally {
            fclose($handle);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
