<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Suggestions\Import\CsvSuggestionImportSource;
use App\Services\LegacyImport\Suggestions\Import\DatabaseSuggestionImportSource;
use App\Services\LegacyImport\Suggestions\Import\SqlSuggestionImportSource;
use App\Services\LegacyImport\Suggestions\SuggestionImportService;

class MigrateSuggestionsCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:suggestions
        {--csv= : Path to a WordPress suggestions CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting suggestions}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WordPress suggested duas (quransunnahduas and suggestedduas) into dua_suggestions';

    public function handle(SuggestionImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — suggestions will not be persisted.');
        }

        $source = $this->resolveSource();
        $report = $importService->import($source, (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.suggestions_report_path'));

        return $this->renderReportSummary($report, $path);
    }

    private function resolveSource(): CsvSuggestionImportSource|SqlSuggestionImportSource|DatabaseSuggestionImportSource
    {
        if ($this->option('csv')) {
            return new CsvSuggestionImportSource((string) $this->option('csv'));
        }

        if ($this->option('sql')) {
            return new SqlSuggestionImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabaseSuggestionImportSource;
    }
}
