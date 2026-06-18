<?php

namespace App\Console\Commands\LegacyImport;

use App\Services\LegacyImport\LegacyImportReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

abstract class LegacyImportCommand extends Command
{
    protected function writeReport(LegacyImportReport $report, string $defaultPath): string
    {
        $path = $this->option('report') ?: $defaultPath;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    protected function renderReportSummary(LegacyImportReport $report, string $path): int
    {
        $counts = $report->toArray()['counts'];

        $this->table(
            ['Metric', 'Count'],
            collect($counts)->map(fn (int $count, string $metric): array => [str_replace('_', ' ', ucfirst($metric)), (string) $count])->values()->all(),
        );

        $this->info("Import report written to {$path}");

        foreach ($report->failed as $failure) {
            $label = $failure['email']
                ?? $failure['slug']
                ?? $failure['title']
                ?? ('WP #'.($failure['wp_post_id'] ?? $failure['wp_legacy_id'] ?? '?'));
            $this->error("{$label}: {$failure['reason']}");
        }

        return $report->failed === [] ? self::SUCCESS : self::FAILURE;
    }

    protected function ensureSingleSource(): void
    {
        $sources = array_filter([
            'csv' => $this->option('csv'),
            'sql' => $this->option('sql'),
            'database' => $this->option('database') ? true : null,
        ]);

        if (count($sources) !== 1) {
            throw new RuntimeException('Specify exactly one source: --csv=, --sql=, or --database.');
        }
    }

    protected function tablePrefix(): string
    {
        return (string) config('database.connections.wordpress.prefix', 'wp_');
    }
}
