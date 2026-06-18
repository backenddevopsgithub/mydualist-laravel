<?php

namespace App\Services;

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Support\Facades\DB;

class SubmissionCounterService extends Service
{
    private static bool $disabled = false;

    /**
     * @var list<string>
     */
    public const COUNTER_COLUMNS = [
        'submissions_count',
        'pending_submissions_count',
        'completed_submissions_count',
        'hidden_submissions_count',
        'archived_submissions_count',
        'reported_submissions_count',
        'non_personal_submissions_count',
    ];

    public static function withoutCounterUpdates(callable $callback): mixed
    {
        $previous = self::$disabled;
        self::$disabled = true;

        try {
            return $callback();
        } finally {
            self::$disabled = $previous;
        }
    }

    public static function isDisabled(): bool
    {
        return self::$disabled;
    }

    public function recordCreated(DuaSubmission $submission): void
    {
        if (! $this->shouldCount($submission)) {
            return;
        }

        $this->adjustCounters((int) $submission->dua_list_id, $this->createDeltas($submission));
    }

    public function recordRemoved(DuaSubmission $submission): void
    {
        if ($submission->dua_list_id === null) {
            return;
        }

        $this->adjustCounters((int) $submission->dua_list_id, $this->removeDeltas($submission));
    }

    public function recordUpdated(DuaSubmission $submission): void
    {
        if ($submission->wasChanged('deleted_at')) {
            return;
        }

        if ($submission->wasChanged('dua_list_id')) {
            $original = $this->snapshotFromOriginal($submission);
            $this->recordRemoved($original);
            $this->recordCreated($submission);

            return;
        }

        if ($submission->wasChanged('status')) {
            $from = $this->normalizeStatus($submission->getOriginal('status'));
            $to = $this->normalizeStatus($submission->status);

            if ($from !== null && $to !== null && $from !== $to) {
                $this->adjustCounters(
                    (int) $submission->dua_list_id,
                    $this->transitionDeltas($from, $to),
                );
            }
        }

        if ($submission->wasChanged('is_personal_dua')) {
            $wasPersonal = (bool) $submission->getOriginal('is_personal_dua');
            $isPersonal = (bool) $submission->is_personal_dua;

            if ($wasPersonal !== $isPersonal) {
                $this->adjustCounters((int) $submission->dua_list_id, [
                    'non_personal_submissions_count' => $isPersonal ? -1 : 1,
                ]);
            }
        }
    }

    /**
     * @return array{lists_processed: int, mismatches: int}
     */
    public function reconcile(?int $listId = null): array
    {
        return self::withoutCounterUpdates(function () use ($listId): array {
            $listsProcessed = 0;
            $mismatches = 0;

            $query = DuaList::query()->select('id')->orderBy('id');

            if ($listId !== null) {
                $query->whereKey($listId);
            }

            $query->chunkById(100, function ($lists) use (&$listsProcessed, &$mismatches): void {
                foreach ($lists as $list) {
                    $listsProcessed++;
                    $expected = $this->aggregateForList((int) $list->id);
                    $current = DuaList::query()
                        ->whereKey($list->id)
                        ->first(array_merge(['id'], self::COUNTER_COLUMNS));

                    if ($current === null) {
                        continue;
                    }

                    $hasMismatch = false;

                    foreach (self::COUNTER_COLUMNS as $column) {
                        if ((int) $current->{$column} !== (int) $expected[$column]) {
                            $hasMismatch = true;

                            break;
                        }
                    }

                    if ($hasMismatch) {
                        $mismatches++;
                    }

                    DuaList::query()->whereKey($list->id)->update($expected);
                }
            });

            return [
                'lists_processed' => $listsProcessed,
                'mismatches' => $mismatches,
            ];
        });
    }

    /**
     * @return array<string, int>
     */
    public function aggregateForList(int $listId): array
    {
        $pending = DuaSubmissionStatus::Pending->value;
        $completed = DuaSubmissionStatus::Completed->value;
        $hidden = DuaSubmissionStatus::Hidden->value;
        $archived = DuaSubmissionStatus::Archived->value;
        $reported = DuaSubmissionStatus::Reported->value;

        $row = DuaSubmission::query()
            ->where('dua_list_id', $listId)
            ->selectRaw(
                'COUNT(*) as submissions_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_submissions_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed_submissions_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as hidden_submissions_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as archived_submissions_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as reported_submissions_count,
                SUM(CASE WHEN is_personal_dua = 0 THEN 1 ELSE 0 END) as non_personal_submissions_count',
                [$pending, $completed, $hidden, $archived, $reported],
            )
            ->first();

        return [
            'submissions_count' => (int) ($row->submissions_count ?? 0),
            'pending_submissions_count' => (int) ($row->pending_submissions_count ?? 0),
            'completed_submissions_count' => (int) ($row->completed_submissions_count ?? 0),
            'hidden_submissions_count' => (int) ($row->hidden_submissions_count ?? 0),
            'archived_submissions_count' => (int) ($row->archived_submissions_count ?? 0),
            'reported_submissions_count' => (int) ($row->reported_submissions_count ?? 0),
            'non_personal_submissions_count' => (int) ($row->non_personal_submissions_count ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function createDeltas(DuaSubmission $submission): array
    {
        $deltas = [
            'submissions_count' => 1,
            'non_personal_submissions_count' => $submission->is_personal_dua ? 0 : 1,
        ];

        $statusColumn = $this->statusColumn($this->normalizeStatus($submission->status));

        if ($statusColumn !== null) {
            $deltas[$statusColumn] = 1;
        }

        return $deltas;
    }

    /**
     * @return array<string, int>
     */
    private function removeDeltas(DuaSubmission $submission): array
    {
        $deltas = [
            'submissions_count' => -1,
            'non_personal_submissions_count' => $submission->is_personal_dua ? 0 : -1,
        ];

        $statusColumn = $this->statusColumn($this->normalizeStatus($submission->status));

        if ($statusColumn !== null) {
            $deltas[$statusColumn] = -1;
        }

        return $deltas;
    }

    /**
     * @return array<string, int>
     */
    private function transitionDeltas(string $from, string $to): array
    {
        $deltas = [];

        $fromColumn = $this->statusColumn($from);
        $toColumn = $this->statusColumn($to);

        if ($fromColumn !== null) {
            $deltas[$fromColumn] = -1;
        }

        if ($toColumn !== null) {
            $deltas[$toColumn] = ($deltas[$toColumn] ?? 0) + 1;
        }

        return $deltas;
    }

    /**
     * @param  array<string, int>  $deltas
     */
    private function adjustCounters(int $listId, array $deltas): void
    {
        $deltas = array_filter(
            $deltas,
            fn (int $delta): bool => $delta !== 0,
        );

        if ($deltas === []) {
            return;
        }

        DB::transaction(function () use ($listId, $deltas): void {
            /** @var DuaList|null $list */
            $list = DuaList::query()->whereKey($listId)->lockForUpdate()->first();

            if ($list === null) {
                return;
            }

            foreach ($deltas as $column => $delta) {
                $list->{$column} = max(0, (int) $list->{$column} + (int) $delta);
            }

            $list->saveQuietly();
        });
    }

    private function shouldCount(DuaSubmission $submission): bool
    {
        return $submission->dua_list_id !== null
            && $submission->deleted_at === null
            && ! $submission->trashed();
    }

    private function snapshotFromOriginal(DuaSubmission $submission): DuaSubmission
    {
        $snapshot = new DuaSubmission([
            'dua_list_id' => $submission->getOriginal('dua_list_id'),
            'status' => $submission->getOriginal('status'),
            'is_personal_dua' => (bool) $submission->getOriginal('is_personal_dua'),
        ]);
        $snapshot->exists = true;

        return $snapshot;
    }

    private function normalizeStatus(mixed $status): ?string
    {
        if ($status instanceof DuaSubmissionStatus) {
            return $status->value;
        }

        if (is_string($status) && $status !== '') {
            return $status;
        }

        return null;
    }

    private function statusColumn(?string $status): ?string
    {
        return match ($status) {
            DuaSubmissionStatus::Pending->value => 'pending_submissions_count',
            DuaSubmissionStatus::Completed->value => 'completed_submissions_count',
            DuaSubmissionStatus::Hidden->value => 'hidden_submissions_count',
            DuaSubmissionStatus::Archived->value => 'archived_submissions_count',
            DuaSubmissionStatus::Reported->value => 'reported_submissions_count',
            default => null,
        };
    }
}
