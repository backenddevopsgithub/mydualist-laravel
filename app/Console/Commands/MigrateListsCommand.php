<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Lists\Import\CsvListImportSource;
use App\Services\LegacyImport\Lists\Import\DatabaseListImportSource;
use App\Services\LegacyImport\Lists\Import\SqlListImportSource;
use App\Services\LegacyImport\Lists\ListImportService;

class MigrateListsCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:lists
        {--csv= : Path to a WordPress dua lists CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting lists or downloading images}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WordPress dua_list posts into dua_lists with cover images and owner preferences';

    public function handle(ListImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — lists and images will not be persisted.');
        }

        $source = $this->resolveSource();
        $report = $importService->import($source, (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.lists_report_path'));

        return $this->renderReportSummary($report, $path);
    }

    private function resolveSource(): CsvListImportSource|SqlListImportSource|DatabaseListImportSource
    {
        if ($this->option('csv')) {
            return new CsvListImportSource((string) $this->option('csv'));
        }

        if ($this->option('sql')) {
            return new SqlListImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabaseListImportSource;
    }
}
