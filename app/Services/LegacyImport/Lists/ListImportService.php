<?php

namespace App\Services\LegacyImport\Lists;

use App\Models\DuaList;
use App\Models\User;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Lists\Import\ListImportSource;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use App\Services\Service;
use Illuminate\Support\Facades\DB;

class ListImportService extends Service
{
    public function __construct(
        private readonly ListCoverImageMigrator $coverImageMigrator,
    ) {}

    public function import(ListImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('lists');
        $batchSize = (int) config('mydualist.legacy.import.batch_size', 100);
        $batch = [];

        foreach ($source->records() as $record) {
            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $report, $dryRun);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->processBatch($batch, $report, $dryRun);
        }

        return $report;
    }

    /**
     * @param  list<WordPressListRecord>  $batch
     */
    private function processBatch(array $batch, LegacyImportReport $report, bool $dryRun): void
    {
        foreach ($batch as $record) {
            try {
                $this->importRecord($record, $report, $dryRun);
            } catch (\Throwable $exception) {
                $report->addFailed($record->summary(), $exception->getMessage());
            }
        }
    }

    private function importRecord(WordPressListRecord $record, LegacyImportReport $report, bool $dryRun): void
    {
        $owner = User::query()->where('wp_legacy_id', $record->ownerWpLegacyId)->first();

        if ($owner === null) {
            $report->addFailed($record->summary(), "Owner wp_legacy_id {$record->ownerWpLegacyId} not found. Import users first.");

            return;
        }

        $existing = DuaList::withTrashed()->where('wp_post_id', $record->wpPostId)->first();
        $slugConflict = DuaList::withTrashed()
            ->where('slug', $record->slug)
            ->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))
            ->exists();

        if ($slugConflict) {
            $report->addFailed($record->summary(), "Slug '{$record->slug}' already belongs to another list.");

            return;
        }

        $coverImagePath = $dryRun
            ? ($existing?->cover_image_path ?? ($record->coverImageUrl ? 'list-covers/dry-run.jpg' : null))
            : $this->coverImageMigrator->migrate($record->coverImageUrl, $record, $report);

        if ($dryRun) {
            if ($existing !== null) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $owner, $existing, $coverImagePath): void {
            $attributes = [
                'user_id' => $owner->id,
                'title' => $record->title,
                'slug' => $record->slug,
                'occasion' => $record->occasion,
                'start_date' => $record->startDate,
                'end_date' => $record->endDate,
                'cover_image_path' => $coverImagePath,
                'list_mode' => $record->listMode,
                'donation_link' => $record->donationLink,
                'donation_note' => $record->donationNote,
                'insights_views' => $record->insightsViews,
                'insights_clicks' => $record->insightsClicks,
                'dua_limit_per_person' => $record->ownerPreferences['dua_limit_per_person'],
                'display_order' => $record->ownerPreferences['display_order'],
                'email_frequency' => $record->ownerPreferences['email_frequency'],
                'status' => $record->status,
                'published_at' => $record->publishedAt,
            ];

            $duaList = DuaList::withTrashed()->updateOrCreate(
                ['wp_post_id' => $record->wpPostId],
                $attributes,
            );

            LegacyImportTimestamps::apply($duaList, $record->createdAt, $record->updatedAt);

            if ($record->isTrashed && ! $duaList->trashed()) {
                $duaList->delete();
            }

            if (! $record->isTrashed && $duaList->trashed()) {
                $duaList->restore();
            }

            if ($existing === null) {
                $report->addImported($record->summary());
            } else {
                $report->addUpdated($record->summary());
            }
        });
    }
}
