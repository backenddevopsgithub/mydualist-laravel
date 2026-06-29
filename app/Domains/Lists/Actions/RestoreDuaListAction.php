<?php

namespace App\Domains\Lists\Actions;

use App\Actions\Action;
use App\Models\DuaList;

class RestoreDuaListAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];

        $updates = [
            'status' => DuaList::STATUS_ACTIVE,
        ];

        if ($duaList->published_at === null) {
            $updates['published_at'] = now();
        }

        $duaList->forceFill($updates)->save();

        return $duaList->fresh();
    }
}
