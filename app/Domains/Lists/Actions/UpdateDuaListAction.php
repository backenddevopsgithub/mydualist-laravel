<?php

namespace App\Domains\Lists\Actions;

use App\Actions\Action;
use App\Models\DuaList;

class UpdateDuaListAction extends Action
{
    /**
     * @param  array{title: string, start_date?: string|null, end_date?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];
        $data = $args[1];

        $duaList->update([
            'title' => $data['title'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);

        return $duaList;
    }
}
