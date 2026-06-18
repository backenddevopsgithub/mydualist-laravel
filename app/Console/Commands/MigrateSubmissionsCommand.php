<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Submissions\Import\CsvSubmissionImportSource;
use App\Services\LegacyImport\Submissions\Import\DatabaseSubmissionImportSource;
use App\Services\LegacyImport\Submissions\Import\SqlSubmissionImportSource;
use App\Services\LegacyImport\Submissions\Import\SubmissionImportSource;
use App\Services\LegacyImport\Submissions\SubmissionImportService;

class MigrateSubmissionsCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:submissions
        {--csv= : Path to a WordPress submissions CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting submissions}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WordPress submission CPT records and reconcile submission locks';

    public function handle(SubmissionImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — submissions will not be persisted.');
        }

        $report = $importService->import($this->resolveSource(), (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.submissions_report_path'));

        $exitCode = $this->renderReportSummary($report, $path);

        if ($report->reconciliation !== []) {
            $this->newLine();
            $this->info('Lock reconciliation:');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Lists processed', (string) ($report->reconciliation['lists_processed'] ?? 0)],
                    ['Submissions updated', (string) ($report->reconciliation['submissions_updated'] ?? 0)],
                    ['Unlocks replayed', (string) ($report->reconciliation['unlocks_replayed'] ?? 0)],
                    ['Mismatches', (string) count($report->reconciliation['mismatches'] ?? [])],
                ],
            );
        }

        return $exitCode;
    }

    private function resolveSource(): SubmissionImportSource
    {
        if ($this->option('csv')) {
            return new CsvSubmissionImportSource((string) $this->option('csv'));
        }

        if ($this->option('sql')) {
            return new SqlSubmissionImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabaseSubmissionImportSource;
    }
}
