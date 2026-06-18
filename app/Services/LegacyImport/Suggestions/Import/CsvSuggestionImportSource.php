<?php

namespace App\Services\LegacyImport\Suggestions\Import;

use App\Services\LegacyImport\Suggestions\WordPressSuggestionRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use Illuminate\Support\Str;
use RuntimeException;

class CsvSuggestionImportSource implements SuggestionImportSource
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

                $record = $this->mapRow($data);

                if ($record !== null) {
                    yield $record->wpPostId => $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  array<string, string|null>  $data
     */
    private function mapRow(array $data): ?WordPressSuggestionRecord
    {
        $wpPostId = (int) ($data['wp_post_id'] ?? $data['id'] ?? 0);
        $title = trim((string) ($data['post_title'] ?? $data['title'] ?? ''));

        if ($wpPostId <= 0 || $title === '') {
            return null;
        }

        $postType = (string) ($data['post_type'] ?? 'quransunnahduas');
        $content = (string) ($data['post_content'] ?? $data['content'] ?? $title);
        $hadith = WordPressValueMapper::nullableString($data['hadith_reference'] ?? null);
        $quran = WordPressValueMapper::nullableString($data['quran_reference'] ?? null);
        $sourceType = WordPressValueMapper::nullableString($data['source_type'] ?? null)
            ?? $this->resolveSourceType($postType, $hadith, $quran);

        return new WordPressSuggestionRecord(
            wpPostId: $wpPostId,
            postType: $postType,
            title: $title,
            content: $content,
            category: WordPressValueMapper::nullableString($data['category'] ?? $data['category_slug'] ?? null)
                ?? 'general',
            sourceType: $sourceType,
            sourceReference: $hadith ?? $quran,
            usedCount: (int) ($data['used_count'] ?? $data['_used'] ?? 0),
            isVisible: ! in_array(strtolower((string) ($data['is_visible'] ?? '1')), ['0', 'false', 'no'], true),
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
