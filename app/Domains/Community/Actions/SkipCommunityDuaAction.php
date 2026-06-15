<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Models\CommunityDua;
use App\Models\User;

class SkipCommunityDuaAction extends Action
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
    ) {}

    public function handle(mixed ...$args): mixed
    {
        /** @var CommunityDua $communityDua */
        $communityDua = $args[0];
        /** @var User $user */
        $user = $args[1];

        return $this->queue->resolveForUser($user, $communityDua, forceNext: true);
    }
}
