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

        $duaList->forceFill([
            'status' => DuaList::STATUS_ACTIVE,
        ])->save();

        return $duaList;
    }
}
