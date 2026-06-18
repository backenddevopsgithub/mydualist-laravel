<?php

namespace App\Console\Commands;

use App\Services\SubmissionCounterService;
use Illuminate\Console\Command;

class ReconcileSubmissionCountersCommand extends Command
{
    protected $signature = 'submissions:reconcile-counters
        {--list= : Reconcile counters for a single dua list ID}';

    protected $description = 'Recompute denormalized submission counters on dua_lists from dua_submissions';

    public function handle(SubmissionCounterService $counters): int
    {
        $listId = $this->option('list');
        $onlyListId = $listId !== null && $listId !== '' ? (int) $listId : null;

        if ($onlyListId !== null && $onlyListId <= 0) {
            $this->error('The --list option must be a positive integer.');

            return self::FAILURE;
        }

        $result = $counters->reconcile($onlyListId);

        $this->info(sprintf(
            'Reconciled counters for %d list(s). %d list(s) had mismatched counters before repair.',
            $result['lists_processed'],
            $result['mismatches'],
        ));

        return self::SUCCESS;
    }
}
