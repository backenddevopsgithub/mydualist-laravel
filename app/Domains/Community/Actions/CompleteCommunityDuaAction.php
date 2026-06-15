<?php

namespace App\Domains\Community\Actions;

use App\Actions\Action;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Enums\CommunityDuaStatus;
use App\Events\CommunityDuaCompletedByPilgrim;
use App\Models\CommunityDua;
use App\Models\CommunityDuaCompletion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompleteCommunityDuaAction extends Action
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

        return DB::transaction(function () use ($communityDua, $user): ?CommunityDua {
            /** @var CommunityDua $locked */
            $locked = CommunityDua::query()->whereKey($communityDua->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== CommunityDuaStatus::Active || ! $locked->is_visible) {
                throw new RuntimeException('This community dua is no longer available.');
            }

            $alreadyCompleted = CommunityDuaCompletion::query()
                ->where('community_dua_id', $locked->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($alreadyCompleted) {
                throw new RuntimeException('You have already completed this community dua.');
            }

            CommunityDuaCompletion::query()->create([
                'community_dua_id' => $locked->id,
                'user_id' => $user->id,
            ]);

            $locked->increment('completion_count');
            $locked->refresh();

            event(new CommunityDuaCompletedByPilgrim($locked->fresh(), $user));

            $fullyComplete = $locked->completion_count >= $locked->required_completions;

            if ($fullyComplete) {
                $locked->forceFill([
                    'status' => CommunityDuaStatus::Completed,
                    'is_visible' => false,
                    'fulfilled_at' => now(),
                ])->save();

                $this->queue->reassignUsersWaitingOn($locked);
            }

            return $this->queue->resolveForUser($user, $locked, forceNext: true);
        });
    }
}
