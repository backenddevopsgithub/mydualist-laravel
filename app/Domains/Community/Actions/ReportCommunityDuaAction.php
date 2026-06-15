<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Enums\CommunityDuaStatus;
use App\Models\CommunityDua;

class ReportCommunityDuaAction extends Action
{
    public function __construct(
        private readonly CommunityDuaQueueService $queue,
    ) {}

    /**
     * @param  array{report_reason: string, report_note?: string|null}  $data
     */
    public function handle(mixed ...$args): mixed
    {
        /** @var CommunityDua $communityDua */
        $communityDua = $args[0];
        $data = $args[1];

        $communityDua->forceFill([
            'status' => CommunityDuaStatus::Reported,
            'is_visible' => false,
            'reported_at' => now(),
            'report_reason' => $data['report_reason'],
            'report_note' => $data['report_note'] ?? null,
        ])->save();

        $this->queue->reassignUsersWaitingOn($communityDua);

        return $communityDua->fresh();
    }
}
