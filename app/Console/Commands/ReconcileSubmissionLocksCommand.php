<?php

namespace App\Console\Commands;

use App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService;
use Illuminate\Console\Command;

class ReconcileSubmissionLocksCommand extends Command
{
    protected $signature = 'submissions:reconcile-locks
        {--dry-run : Report mismatches without updating rows}';

    protected $description = 'Recompute is_locked flags on dua_submissions from entitlements and submission order';

    public function handle(SubmissionLockReconciliationService $locks): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $locks->reconcile($dryRun);

        $this->info(sprintf(
            'Processed %d list(s). Updated %d submission(s). Found %d mismatch(es).',
            $result['lists_processed'],
            $result['submissions_updated'],
            count($result['mismatches']),
        ));

        if ($result['mismatches'] !== [] && $this->output->isVerbose()) {
            foreach ($result['mismatches'] as $mismatch) {
                $this->line(json_encode($mismatch));
            }
        }

        if (! $dryRun && $result['unlocks_replayed'] > 0) {
            $this->info(sprintf('Replayed purchase unlocks for %d submission(s).', $result['unlocks_replayed']));
        }

        return self::SUCCESS;
    }
}
