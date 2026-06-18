<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Purchases\Import\CsvPurchaseImportSource;
use App\Services\LegacyImport\Purchases\Import\DatabasePurchaseImportSource;
use App\Services\LegacyImport\Purchases\Import\PurchaseImportSource;
use App\Services\LegacyImport\Purchases\Import\SqlPurchaseImportSource;
use App\Services\LegacyImport\Purchases\PurchaseImportService;

class MigratePurchasesCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:purchases
        {--csv= : Path to a WooCommerce orders CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Validate and report without persisting purchases}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WooCommerce orders into billing purchases and entitlement grants';

    public function handle(PurchaseImportService $importService): int
    {
        $this->ensureSingleSource();

        if ($this->option('dry-run')) {
            $this->warn('Dry run — purchases will not be persisted.');
        }

        $report = $importService->import($this->resolveSource(), (bool) $this->option('dry-run'));
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.purchases_report_path'));

        return $this->renderReportSummary($report, $path);
    }

    private function resolveSource(): PurchaseImportSource
    {
        if ($this->option('csv')) {
            return new CsvPurchaseImportSource((string) $this->option('csv'));
        }

        if ($this->option('sql')) {
            return new SqlPurchaseImportSource((string) $this->option('sql'), $this->tablePrefix());
        }

        return new DatabasePurchaseImportSource;
    }
}
