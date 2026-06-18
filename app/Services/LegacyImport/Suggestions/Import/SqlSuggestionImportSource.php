<?php

namespace App\Services\LegacyImport\Suggestions\Import;

use App\Services\LegacyImport\Suggestions\WordPressSuggestionRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\SqlDumpReader;
use Illuminate\Support\Str;

class SqlSuggestionImportSource implements SuggestionImportSource
{
    /**
     * @var list<string>
     */
    private array $postTypes = ['quransunnahduas', 'suggestedduas'];

    private SqlDumpReader $reader;

    public function __construct(string $path, string $tablePrefix = 'wp_')
    {
        $this->reader = new SqlDumpReader($path, $tablePrefix);
    }

    public function records(): iterable
    {
        foreach ($this->reader->postsById() as $postId => $post) {
            $postType = (string) ($post['post_type'] ?? '');

            if (! in_array($postType, $this->postTypes, true) || ($post['post_status'] ?? '') !== 'publish') {
                continue;
            }

            $meta = $this->reader->postmetaByPostId()[$postId] ?? [];
            $category = $this->reader->primaryTaxonomyTermForPost($postId, 'dua_category');

            yield $postId => $this->mapPost(
                $postId,
                $postType,
                (string) ($post['post_title'] ?? ''),
                (string) ($post['post_content'] ?? ''),
                $meta,
                $category,
            );
        }
    }

    /**
     * @param  array<string, string>  $meta
     * @param  array{name: string, slug: string}|null  $category
     */
    private function mapPost(
        int $postId,
        string $postType,
        string $title,
        string $content,
        array $meta,
        ?array $category,
    ): WordPressSuggestionRecord {
        $hadith = WordPressValueMapper::nullableString($meta['hadith_reference'] ?? null);
        $quran = WordPressValueMapper::nullableString($meta['quran_reference'] ?? null);
        $sourceReference = $hadith ?? $quran;

        return new WordPressSuggestionRecord(
            wpPostId: $postId,
            postType: $postType,
            title: $title !== '' ? $title : 'Untitled Dua',
            content: $content !== '' ? $content : $title,
            category: $category !== null ? ($category['slug'] !== '' ? $category['slug'] : Str::slug($category['name'])) : 'general',
            sourceType: $this->resolveSourceType($postType, $hadith, $quran),
            sourceReference: $sourceReference,
            usedCount: (int) ($meta['_used'] ?? 0),
            isVisible: true,
        );
    }

    private function resolveSourceType(string $postType, ?string $hadith, ?string $quran): string
    {
        if ($postType === 'suggestedduas') {
            return 'general';
        }

        if ($quran !== null && $hadith === null) {
            return 'quran';
        }

        if ($hadith !== null) {
            return 'sunnah';
        }

        return 'quran';
    }
}
