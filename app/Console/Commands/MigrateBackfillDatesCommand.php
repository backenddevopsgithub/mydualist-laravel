<?php

namespace App\Console\Commands;

use App\Console\Commands\LegacyImport\LegacyImportCommand;
use App\Services\LegacyImport\Validation\LegacyDateBackfillService;
use RuntimeException;

class MigrateBackfillDatesCommand extends LegacyImportCommand
{
    protected $signature = 'migrate:backfill-dates
        {--entity=all : Entity to backfill: all, users, lists, submissions, purchases, community_duas, blog_posts}
        {--dry-run : Report counts without updating timestamps}';

    protected $description = 'Backfill WordPress created_at timestamps for already-imported legacy records';

    public function handle(LegacyDateBackfillService $backfill): int
    {
        $entity = (string) $this->option('entity');
        $dryRun = (bool) $this->option('dry-run');

        $allowed = ['all', 'users', 'lists', 'submissions', 'purchases', 'community_duas', 'blog_posts'];

        if (! in_array($entity, $allowed, true)) {
            throw new RuntimeException('Invalid --entity value. Allowed: '.implode(', ', $allowed));
        }

        if ($dryRun) {
            $this->warn('Dry run — timestamps will not be updated.');
        }

        $counts = $backfill->backfill($dryRun, $entity === 'all' ? null : $entity);

        $this->table(
            ['Entity', 'Records '.($dryRun ? 'matched' : 'updated')],
            collect($counts)->map(fn (int $count, string $name): array => [$name, (string) $count])->values()->all(),
        );

        $this->info($dryRun
            ? 'Dry run complete. Re-run without --dry-run to apply timestamps.'
            : 'Timestamp backfill complete.');

        return self::SUCCESS;
    }
}
