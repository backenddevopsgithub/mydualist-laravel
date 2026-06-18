<?php

namespace App\Services\Blog\Import;

use App\Services\Blog\WordPressPostRecord;
use App\Support\WordPress\SqlInsertParser;
use Carbon\Carbon;
use RuntimeException;

class SqlDumpBlogImportSource implements BlogImportSource
{
    public function __construct(
        private readonly string $path,
        private readonly string $tablePrefix = 'wp_',
    ) {}

    public function records(): iterable
    {
        if (! is_readable($this->path)) {
            throw new RuntimeException("SQL import file is not readable: {$this->path}");
        }

        $sql = file_get_contents($this->path);

        if ($sql === false) {
            throw new RuntimeException("Unable to read SQL import file: {$this->path}");
        }

        $postsTable = $this->tablePrefix.'posts';
        $postmetaTable = $this->tablePrefix.'postmeta';
        $termsTable = $this->tablePrefix.'terms';
        $termTaxonomyTable = $this->tablePrefix.'term_taxonomy';
        $termRelationshipsTable = $this->tablePrefix.'term_relationships';

        $postRows = SqlInsertParser::parseTableRows($sql, $postsTable);
        $postmetaRows = SqlInsertParser::parseTableRows($sql, $postmetaTable);
        $termRows = SqlInsertParser::parseTableRows($sql, $termsTable);
        $termTaxonomyRows = SqlInsertParser::parseTableRows($sql, $termTaxonomyTable);
        $termRelationshipRows = SqlInsertParser::parseTableRows($sql, $termRelationshipsTable);

        $postsById = $this->indexPosts($postRows);
        $postmeta = $this->indexPostmeta($postmetaRows);
        $categoriesByPostId = $this->indexCategories(
            $termRows,
            $termTaxonomyRows,
            $termRelationshipRows,
        );

        foreach ($postsById as $postId => $post) {
            if (($post['post_type'] ?? '') !== 'post' || ($post['post_status'] ?? '') !== 'publish') {
                continue;
            }

            $title = trim((string) ($post['post_title'] ?? ''));
            $slug = trim((string) ($post['post_name'] ?? ''));
            $content = (string) ($post['post_content'] ?? '');

            if ($title === '' || $slug === '' || $content === '') {
                continue;
            }

            $meta = $postmeta[$postId] ?? [];
            $category = $categoriesByPostId[$postId] ?? null;
            $featuredImageUrl = $this->resolveFeaturedImageUrl($meta, $postsById);

            yield $postId => new WordPressPostRecord(
                wpPostId: $postId,
                title: $title,
                slug: $slug,
                content: $content,
                excerpt: $this->nullableString($post['post_excerpt'] ?? null),
                publishedAt: $this->parseDate($post['post_date'] ?? null),
                featuredImageUrl: $featuredImageUrl,
                categorySlug: $category['slug'] ?? null,
                categoryName: $category['name'] ?? null,
                metaTitle: $this->nullableString($meta['_yoast_wpseo_title'] ?? null),
                metaDescription: $this->nullableString($meta['_yoast_wpseo_metadesc'] ?? null),
                ogImageUrl: $this->nullableString($meta['_yoast_wpseo_opengraph-image'] ?? null),
            );
        }
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string|null>>
     */
    private function indexPosts(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['ID'])) {
                $indexed[(int) $row['ID']] = $row;

                continue;
            }

            if (isset($row[0])) {
                $indexed[(int) $row[0]] = [
                    'ID' => (string) $row[0],
                    'post_author' => $row[1] ?? null,
                    'post_date' => $row[2] ?? null,
                    'post_date_gmt' => $row[3] ?? null,
                    'post_content' => $row[4] ?? null,
                    'post_title' => $row[5] ?? null,
                    'post_excerpt' => $row[6] ?? null,
                    'post_status' => $row[7] ?? null,
                    'post_name' => $row[11] ?? null,
                    'post_type' => $row[19] ?? null,
                    'guid' => $row[18] ?? null,
                ];
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $rows
     * @return array<int, array<string, string>>
     */
    private function indexPostmeta(array $rows): array
    {
        $indexed = [];

        foreach ($rows as $row) {
            if (isset($row['post_id'], $row['meta_key'])) {
                $indexed[(int) $row['post_id']][(string) $row['meta_key']] = (string) ($row['meta_value'] ?? '');

                continue;
            }

            if (isset($row[1], $row[2])) {
                $indexed[(int) $row[1]][(string) $row[2]] = (string) ($row[3] ?? '');
            }
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, string|null>|list<string|null>>  $termRows
     * @param  list<array<string, string|null>|list<string|null>>  $taxonomyRows
     * @param  list<array<string, string|null>|list<string|null>>  $relationshipRows
     * @return array<int, array{name: string, slug: string}>
     */
    private function indexCategories(array $termRows, array $taxonomyRows, array $relationshipRows): array
    {
        $terms = [];

        foreach ($termRows as $row) {
            if (isset($row['term_id'])) {
                $terms[(int) $row['term_id']] = [
                    'name' => (string) ($row['name'] ?? ''),
                    'slug' => (string) ($row['slug'] ?? ''),
                ];

                continue;
            }

            if (isset($row[0])) {
                $terms[(int) $row[0]] = [
                    'name' => (string) ($row[1] ?? ''),
                    'slug' => (string) ($row[2] ?? ''),
                ];
            }
        }

        $taxonomyById = [];

        foreach ($taxonomyRows as $row) {
            if (isset($row['term_taxonomy_id'], $row['taxonomy'], $row['term_id'])) {
                if ($row['taxonomy'] !== 'category') {
                    continue;
                }

                $taxonomyById[(int) $row['term_taxonomy_id']] = (int) $row['term_id'];

                continue;
            }

            if (isset($row[0], $row[2], $row[1]) && $row[2] === 'category') {
                $taxonomyById[(int) $row[0]] = (int) $row[1];
            }
        }

        $categoriesByPostId = [];

        foreach ($relationshipRows as $row) {
            $objectId = isset($row['object_id']) ? (int) $row['object_id'] : (isset($row[1]) ? (int) $row[1] : 0);
            $taxonomyId = isset($row['term_taxonomy_id']) ? (int) $row['term_taxonomy_id'] : (isset($row[2]) ? (int) $row[2] : 0);

            if ($objectId <= 0 || $taxonomyId <= 0 || ! isset($taxonomyById[$taxonomyId], $terms[$taxonomyById[$taxonomyId]])) {
                continue;
            }

            $categoriesByPostId[$objectId] = $terms[$taxonomyById[$taxonomyId]];
        }

        return $categoriesByPostId;
    }

    /**
     * @param  array<string, string>  $meta
     * @param  array<int, array<string, string|null>>  $postsById
     */
    private function resolveFeaturedImageUrl(array $meta, array $postsById): ?string
    {
        $thumbnailId = (int) ($meta['_thumbnail_id'] ?? 0);

        if ($thumbnailId <= 0) {
            return null;
        }

        $attachment = $postsById[$thumbnailId] ?? null;

        if ($attachment === null) {
            return null;
        }

        return $this->nullableString($attachment['guid'] ?? null);
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
