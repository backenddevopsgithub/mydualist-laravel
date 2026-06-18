<?php

namespace App\Services\LegacyImport\Lists\Import;

use App\Models\DuaList;
use App\Services\LegacyImport\Lists\WordPressListRecord;
use App\Services\LegacyImport\Support\WordPressValueMapper;
use RuntimeException;

class CsvListImportSource implements ListImportSource
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
    private function mapRow(array $data): ?WordPressListRecord
    {
        $wpPostId = (int) ($data['wp_post_id'] ?? $data['id'] ?? 0);
        $ownerWpId = (int) ($data['owner_wp_legacy_id'] ?? $data['user'] ?? $data['owner_id'] ?? 0);
        $slug = trim((string) ($data['post_name'] ?? $data['slug'] ?? ''));
        $title = trim((string) ($data['post_title'] ?? $data['title'] ?? ''));

        if ($wpPostId <= 0 || $ownerWpId <= 0 || $slug === '' || $title === '') {
            return null;
        }

        $isActive = ! in_array(strtolower((string) ($data['status'] ?? '1')), ['0', 'archived', 'inactive', 'false'], true);
        $postDate = WordPressValueMapper::parseDateTime($data['post_date'] ?? $data['published_at'] ?? null);

        $ownerPreferences = [
            'dua_limit_per_person' => WordPressValueMapper::normalizeDuaLimitPerPerson($data['dua_limit_per_person'] ?? null),
            'display_order' => WordPressValueMapper::normalizeDisplayOrder($data['dua_display_order'] ?? $data['display_order'] ?? null),
            'email_frequency' => WordPressValueMapper::normalizeEmailFrequency($data['frequency_of_emails'] ?? $data['email_frequency'] ?? null),
        ];

        $listMode = WordPressValueMapper::nullableString($data['listmode'] ?? $data['list_mode'] ?? null);

        return new WordPressListRecord(
            wpPostId: $wpPostId,
            ownerWpLegacyId: $ownerWpId,
            title: $title,
            slug: $slug,
            occasion: WordPressValueMapper::normalizeOccasion($data['category'] ?? $data['occasion'] ?? null),
            startDate: WordPressValueMapper::parseDate($data['tripstart'] ?? $data['start_date'] ?? null),
            endDate: WordPressValueMapper::parseDate($data['tripend'] ?? $data['end_date'] ?? null),
            coverImageUrl: WordPressValueMapper::nullableString($data['cover_image_url'] ?? $data['list_image_url'] ?? null),
            status: $isActive ? DuaList::STATUS_ACTIVE : DuaList::STATUS_ARCHIVED,
            publishedAt: $isActive ? ($postDate ?? now()) : null,
            isTrashed: in_array(strtolower((string) ($data['post_status'] ?? '')), ['trash', 'deleted'], true),
            ownerPreferences: $ownerPreferences,
            listMode: $listMode === 'creator' ? 'creator' : null,
            donationLink: WordPressValueMapper::nullableString($data['donationlink'] ?? $data['donation_link'] ?? null),
            donationNote: WordPressValueMapper::nullableString($data['donationnote'] ?? $data['donation_note'] ?? null),
            insightsViews: (int) ($data['insights_views'] ?? $data['_insights_views'] ?? 0),
            insightsClicks: (int) ($data['insights_clicks'] ?? $data['_insights_clicks'] ?? 0),
            createdAt: $postDate,
            updatedAt: WordPressValueMapper::parseDateTime($data['post_modified'] ?? $data['updated_at'] ?? null),
        );
    }
}
