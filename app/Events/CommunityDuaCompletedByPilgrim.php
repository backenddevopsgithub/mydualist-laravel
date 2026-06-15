<?php

namespace App\Events;

use App\Models\CommunityDua;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityDuaCompletedByPilgrim
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly CommunityDua $communityDua,
        public readonly User $completedBy,
    ) {}
}
