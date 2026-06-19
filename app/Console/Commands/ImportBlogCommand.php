<?php

namespace App\Console\Commands;

use App\Services\Blog\BlogImportReport;
use App\Services\Blog\BlogImportService;
use App\Services\Blog\Import\CsvBlogImportSource;
use App\Services\Blog\Import\DatabaseBlogImportSource;
use App\Services\Blog\Import\SqlDumpBlogImportSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportBlogCommand extends Command
{
    protected $signature = 'blog:import
        {--csv= : Path to a WordPress CSV export}
        {--sql= : Path to a WordPress SQL dump}
        {--database : Import directly from the configured WordPress database connection}
        {--dry-run : Transform and report without persisting posts or downloading images}
        {--report= : Output path for the JSON import report}';

    protected $description = 'Import WordPress blog posts with transformed HTML and local assets';

    public function handle(BlogImportService $importService): int
    {
        $source = $this->resolveSource();
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — posts and images will not be persisted.');
        }

        $report = $importService->import($source, $dryRun);
        $reportPath = $this->writeReport($report);

        $counts = $report->toArray()['counts'];

        $this->table(
            ['Metric', 'Count'],
            collect($counts)->map(fn (int $count, string $metric): array => [str_replace('_', ' ', ucfirst($metric)), (string) $count])->values()->all(),
        );

        $this->info("Import report written to {$reportPath}");

        foreach ($report->failed as $failure) {
            $label = $failure['title'] ?? $failure['slug'] ?? ('WP #'.$failure['wp_post_id']);
            $this->error("{$label}: {$failure['reason']}");
        }

        return $report->failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function resolveSource(): CsvBlogImportSource|SqlDumpBlogImportSource|DatabaseBlogImportSource
    {
        $sources = array_filter([
            'csv' => $this->option('csv'),
            'sql' => $this->option('sql'),
            'database' => $this->option('database') ? true : null,
        ]);

        if (count($sources) !== 1) {
            throw new RuntimeException('Specify exactly one source: --csv=, --sql=, or --database.');
        }

        if (isset($sources['csv'])) {
            return new CsvBlogImportSource((string) $sources['csv']);
        }

        if (isset($sources['sql'])) {
            return new SqlDumpBlogImportSource(
                (string) $sources['sql'],
                (string) config('database.connections.wordpress.prefix', ''),
            );
        }

        return new DatabaseBlogImportSource;
    }

    private function writeReport(BlogImportReport $report): string
    {
        $path = $this->option('report')
            ?: (string) config('mydualist.blog.import.report_path');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $path;
    }
}
