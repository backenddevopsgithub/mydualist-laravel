<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\CommunityDuas\CommunityDuaImportService;
use App\Services\LegacyImport\CommunityDuas\Import\CommunityDuaImportSource;
use App\Services\LegacyImport\CommunityDuas\Import\CsvCommunityDuaImportSource;
use App\Services\LegacyImport\CommunityDuas\Import\DatabaseCommunityDuaImportSource;
use App\Services\LegacyImport\CommunityDuas\Import\SqlCommunityDuaImportSource;
use Illuminate\Support\Str;

class MigrateCommunityDuasCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:community-duas
        {--csv= : Path to a community duas CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting community duas}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import community dua CPT records and user queue state';

    public function handle(CommunityDuaImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — community duas will not be persisted.');
        }

        $report = $importService->import($this->resolveSource(), (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.community_duas_report_path'));

        return $this->renderReportSummary($report, $path);
    }

    private function resolveSource(): CommunityDuaImportSource
    {
        if ($this->option('csv')) {
            $csvPath = (string) $this->option('csv');
            $queuePath = Str::replaceLast('.csv', '-queue.csv', $csvPath);

            return new CsvCommunityDuaImportSource(
                $csvPath,
                is_readable($queuePath) ? $queuePath : null,
            );
        }

        if ($this->option('sql')) {
            return new SqlCommunityDuaImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabaseCommunityDuaImportSource;
    }
}
