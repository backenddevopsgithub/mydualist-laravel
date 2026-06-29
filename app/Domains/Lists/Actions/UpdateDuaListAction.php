<?php

namespace App\Domains\Lists\Actions;

use App\Actions\Action;
use App\Domains\Lists\Support\DuaListAvailability;
use App\Models\DuaList;

class UpdateDuaListAction extends Action
{
    public function __construct(
        private readonly DuaListAvailability $availability,
    ) {}

    /**
     * @param  array{title: string, start_date?: string|null, end_date?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        $data = $args[1];

        $updates = [
            'title' => $data['title'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ];

        $duaList->fill($updates);

        if ($this->shouldReopenAfterExtendingEndDate($duaList)) {
            $updates['status'] = DuaList::STATUS_ACTIVE;

            if ($duaList->published_at === null) {
                $updates['published_at'] = now();
            }
        }

        $duaList->update($updates);

        return $duaList->fresh();
    }

    private function shouldReopenAfterExtendingEndDate(DuaList $duaList): bool
    {
        return $duaList->isArchived()
            && ! $this->availability->isExpired($duaList);
    }
}
