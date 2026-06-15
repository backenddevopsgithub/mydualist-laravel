<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Domains\Community\Services\CommunityDuaQueueService;

class CreateFreeCommunityDuaAction extends Action
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
    ) {}

    /**
     * @param  array{first_name: string, last_name: string, email: string, gender: string, content: string}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        $data = $args[0];

        $communityDua = CommunityDua::query()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'gender' => $data['gender'],
            'content' => $data['content'],
            'type' => CommunityDuaType::Free,
            'status' => CommunityDuaStatus::Active,
            'required_completions' => CommunityDuaType::Free->requiredCompletions(),
            'completion_count' => 0,
            'is_visible' => true,
        ]);

        $this->queue->notifyWaitingUsersOfNewDua($communityDua);

        return $communityDua;
    }
}
