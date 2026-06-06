<?php

namespace App\Domains\Lists\Actions;

use App\Actions\Action;
use App\Models\DuaList;

class DeleteDuaListAction extends Action
{
    public function handle(mixed ...$args): mixed
    {
        /** @var DuaList $duaList */
        $duaList = $args[0];

        return $duaList->delete();
    }
}
