<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Users\Import\CsvUserImportSource;
use App\Services\LegacyImport\Users\Import\DatabaseUserImportSource;
use App\Services\LegacyImport\Users\Import\SqlUserImportSource;
use App\Services\LegacyImport\Users\UserImportService;

class MigrateUsersCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:users
        {--csv= : Path to a WordPress users CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting users}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WordPress users into Laravel with legacy password bridge support';

    public function handle(UserImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — users will not be persisted.');
        }

        $source = $this->resolveSource();
        $report = $importService->import($source, (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.users_report_path'));

        return $this->renderReportSummary($report, $path);
    }

    private function resolveSource(): CsvUserImportSource|SqlUserImportSource|DatabaseUserImportSource
    {
        if ($this->option('csv')) {
            return new CsvUserImportSource((string) $this->option('csv'));
        }

        if ($this->option('sql')) {
            return new SqlUserImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabaseUserImportSource;
    }
}
