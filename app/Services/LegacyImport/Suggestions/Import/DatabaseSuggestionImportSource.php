<?php

namespace App\Services\LegacyImport\Suggestions\Import;

use App\Services\LegacyImport\Suggestions\WordPressSuggestionRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use App\Support\WordPress\WordPressConnection;
use Illuminate\Support\Str;

class DatabaseSuggestionImportSource implements SuggestionImportSource
{
    /**
     * @var list<string>
     */
    private array $postTypes = ['quransunnahduas', 'suggestedduas'];

    public function records(): iterable
    {
        $connection = WordPressConnection::connection();
        $prefix = WordPressConnection::prefix();

        $posts = $connection->table("{$prefix}posts")
            ->whereIn('post_type', $this->postTypes)
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get([
                'ID',
                'post_type',
                'post_title',
                'post_content',
            ]);

        foreach ($posts as $post) {
            $postId = (int) $post->ID;
            $meta = $this->postMeta($connection, $prefix, $postId);
            $category = $this->primaryCategory($connection, $prefix, $postId);

            yield $postId => $this->mapPost(
                $postId,
                (string) $post->post_type,
                (string) $post->post_title,
                (string) $post->post_content,
                $meta,
                $category,
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function postMeta(\Illuminate\Database\Connection $connection, string $prefix, int $postId): array
    {
        return $connection->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->pluck('meta_value', 'meta_key')
            ->map(fn ($value): string => (string) $value)
            ->all();
    }

    /**
     * @return array{name: string, slug: string}|null
     */
    private function primaryCategory(\Illuminate\Database\Connection $connection, string $prefix, int $postId): ?array
    {
        $term = $connection->table("{$prefix}term_relationships as tr")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->join("{$prefix}terms as t", 't.term_id', '=', 'tt.term_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'dua_category')
            ->orderBy('tt.term_id')
            ->select(['t.name', 't.slug'])
            ->first();

        if ($term === null) {
            return null;
        }

        return [
            'name' => (string) $term->name,
            'slug' => (string) $term->slug,
        ];
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
