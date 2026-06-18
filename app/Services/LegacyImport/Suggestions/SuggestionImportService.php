<?php

namespace App\Services\LegacyImport\Suggestions;

use App\Models\DuaSuggestion;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Suggestions\Import\SuggestionImportSource;
use App\Services\Service;
use Illuminate\Support\Facades\DB;

class SuggestionImportService extends Service
{
    public function import(SuggestionImportSource $source, bool $dryRun = false): LegacyImportReport
    {
        $report = new LegacyImportReport('suggestions');
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
     * @param  list<WordPressSuggestionRecord>  $batch
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

    private function importRecord(WordPressSuggestionRecord $record, LegacyImportReport $report, bool $dryRun): void
    {
        $existing = DuaSuggestion::query()->where('wp_post_id', $record->wpPostId)->exists();

        if ($dryRun) {
            if ($existing) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }

            return;
        }

        DB::transaction(function () use ($record, $report, $existing): void {
            DuaSuggestion::query()->updateOrCreate(
                ['wp_post_id' => $record->wpPostId],
                [
                    'title' => $record->title,
                    'category' => $record->category,
                    'content' => $record->content,
                    'source_type' => $record->sourceType,
                    'source_reference' => $record->sourceReference,
                    'is_visible' => $record->isVisible,
                    'sort_order' => 0,
                    'used_count' => $record->usedCount,
                ],
            );

            if ($existing) {
                $report->addUpdated($record->summary());
            } else {
                $report->addImported($record->summary());
            }
        });
    }
}
