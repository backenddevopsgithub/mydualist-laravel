<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Validation\MigrationValidationService;

class MigrateValidateCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:validate
        {--report= : Output path for the JSON validation report}';

    protected $description = 'Validate imported legacy data integrity and entitlement accuracy';

    public function handle(MigrationValidationService $validationService): int
    {
        $report = $validationService->validate();
        $path = $this->writeReport($report, (string) config('mydualist.legacy.import.validate_report_path'));

        return $this->renderValidationSummary($report, $path);
    }

    private function renderValidationSummary(LegacyImportReport $report, string $path): int
    {
        $validation = $report->validation;
        $totals = $validation['totals'] ?? [];

        $this->table(
            ['Entity', 'Count'],
            collect($totals)->map(fn (int $count, string $metric): array => [str_replace('_', ' ', ucfirst($metric)), (string) $count])->values()->all(),
        );

        $this->info("Validation report written to {$path}");

        foreach ($validation['failures'] ?? [] as $failure) {
            $this->error('Failure: '.json_encode($failure));
        }

        foreach ($validation['warnings'] ?? [] as $warning) {
            $this->warn('Warning: '.json_encode($warning));
        }

        foreach ($validation['mismatches'] ?? [] as $mismatch) {
            $this->warn('Mismatch: '.json_encode($mismatch));
        }

        if ($validation['passed'] ?? false) {
            $this->info('Validation passed.');

            return self::SUCCESS;
        }

        $this->error('Validation failed.');

        return self::FAILURE;
    }
}
