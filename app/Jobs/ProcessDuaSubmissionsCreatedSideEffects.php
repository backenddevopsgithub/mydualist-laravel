<?php

namespace App\Jobs;

use App\Events\DuaSubmissionsCreated;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;

class ProcessDuaSubmissionsCreatedSideEffects implements ShouldQueue
{
    use Queueable;

    /**
     * @param  list<int>  $submissionIds
     */
    public function __construct(
        public readonly int $duaListId,
        public readonly array $submissionIds,
        public readonly int $nonPersonalCountBefore,
    ) {}

    public function handle(): void
    {
        $duaList = DuaList::query()->find($this->duaListId);

        if ($duaList === null || $this->submissionIds === []) {
            return;
        }

        /** @var Collection<int, DuaSubmission> $submissions */
        $submissions = DuaSubmission::query()
            ->whereIn('id', $this->submissionIds)
            ->orderBy('id')
            ->get();

        if ($submissions->isEmpty()) {
            return;
        }

        event(new DuaSubmissionsCreated($duaList, $submissions, $this->nonPersonalCountBefore));
    }
}
