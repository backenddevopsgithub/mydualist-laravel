<?php

namespace App\Services\Blog;

class BlogImportReport
{
    /**
     * @var list<array{wp_post_id: int, slug: string, title: string}>
     */
    public array $imported = [];

    /**
     * @var list<array{wp_post_id: int, slug: string, title: string}>
     */
    public array $updated = [];

    /**
     * @var list<array{wp_post_id: int|null, slug: string|null, title: string|null, reason: string}>
     */
    public array $failed = [];

    /**
     * @var list<array{wp_post_id: int, slug: string, shortcodes: list<string>}>
     */
    public array $brokenShortcodes = [];

    /**
     * @var list<array{wp_post_id: int, slug: string, url: string}>
     */
    public array $missingImages = [];

    public function addImported(WordPressPostRecord $record): void
    {
        $this->imported[] = $this->summary($record);
    }

    public function addUpdated(WordPressPostRecord $record): void
    {
        $this->updated[] = $this->summary($record);
    }

    public function addFailed(?WordPressPostRecord $record, string $reason): void
    {
        $this->failed[] = [
            'wp_post_id' => $record?->wpPostId,
            'slug' => $record?->slug,
            'title' => $record?->title,
            'reason' => $reason,
        ];
    }

    /**
     * @param  list<string>  $shortcodes
     */
    public function addBrokenShortcodes(WordPressPostRecord $record, array $shortcodes): void
    {
        if ($shortcodes === []) {
            return;
        }

        $this->brokenShortcodes[] = [
            'wp_post_id' => $record->wpPostId,
            'slug' => $record->slug,
            'shortcodes' => $shortcodes,
        ];
    }

    public function addMissingImage(WordPressPostRecord $record, string $url): void
    {
        $this->missingImages[] = [
            'wp_post_id' => $record->wpPostId,
            'slug' => $record->slug,
            'url' => $url,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'counts' => [
                'imported' => count($this->imported),
                'updated' => count($this->updated),
                'failed' => count($this->failed),
                'broken_shortcodes' => count($this->brokenShortcodes),
                'missing_images' => count($this->missingImages),
            ],
            'imported' => $this->imported,
            'updated' => $this->updated,
            'failed' => $this->failed,
            'broken_shortcodes' => $this->brokenShortcodes,
            'missing_images' => $this->missingImages,
        ];
    }

    /**
     * @return array{wp_post_id: int, slug: string, title: string}
     */
    private function summary(WordPressPostRecord $record): array
    {
        return [
            'wp_post_id' => $record->wpPostId,
            'slug' => $record->slug,
            'title' => $record->title,
        ];
    }
}
