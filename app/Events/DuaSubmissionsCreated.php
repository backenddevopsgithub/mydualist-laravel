<?php

namespace App\Events;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DuaSubmissionsCreated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  Collection<int, DuaSubmission>  $submissions
     */
    public function __construct(
        public readonly DuaList $duaList,
        public readonly Collection $submissions,
        public readonly int $nonPersonalCountBefore,
    ) {}
}
