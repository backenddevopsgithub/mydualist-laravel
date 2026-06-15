<?php

namespace App\Events;

use App\Models\DuaList;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DuaListCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DuaList $duaList,
    ) {}
}
