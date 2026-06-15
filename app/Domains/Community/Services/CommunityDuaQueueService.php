<?php

namespace App\Domains\Community\Services;

use App\Enums\CommunityDuaType;
use App\Models\CommunityDua;
use App\Models\CommunityDuaQueueState;
use App\Models\CommunityDuaSkip;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Collection;

class CommunityDuaQueueService extends Service
{
    public function resolveForUser(User $user, ?CommunityDua $current = null, bool $forceNext = false): ?CommunityDua
    {
        $state = $this->queueStateFor($user);

        if (! $forceNext && $state->current_community_dua_id) {
            $assigned = CommunityDua::query()->find($state->current_community_dua_id);

            if ($assigned && $this->isEligibleForUser($assigned, $user)) {
                return $assigned;
            }
        }

        if ($forceNext && $current !== null) {
            $this->recordSkip($user, $current);
        }

        $next = $this->pickNextDua($user, $state);

        $state->forceFill([
            'current_community_dua_id' => $next?->id,
        ])->save();

        return $next;
    }

    public function recordSkip(User $user, CommunityDua $communityDua): void
    {
        CommunityDuaSkip::query()->firstOrCreate([
            'community_dua_id' => $communityDua->id,
            'user_id' => $user->id,
        ]);
    }

    public function clearCurrentForUsersAssignedTo(CommunityDua $communityDua): void
    {
        CommunityDuaQueueState::query()
            ->where('current_community_dua_id', $communityDua->id)
            ->update(['current_community_dua_id' => null]);
    }

    public function reassignUsersWaitingOn(CommunityDua $communityDua): void
    {
        $states = CommunityDuaQueueState::query()
            ->where('current_community_dua_id', $communityDua->id)
            ->with('user')
            ->get();

        foreach ($states as $state) {
            if ($state->user === null) {
                continue;
            }

            $this->resolveForUser($state->user, $communityDua, forceNext: true);
        }
    }

    public function notifyWaitingUsersOfNewDua(CommunityDua $communityDua): void
    {
        CommunityDuaQueueState::query()
            ->whereNull('current_community_dua_id')
            ->update(['current_community_dua_id' => $communityDua->id]);
    }

    private function queueStateFor(User $user): CommunityDuaQueueState
    {
        return CommunityDuaQueueState::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['showing_type' => 'paid', 'pattern' => 0],
        );
    }

    private function isEligibleForUser(CommunityDua $communityDua, User $user): bool
    {
        if (! $communityDua->is_visible || $communityDua->status->value !== 'active') {
            return false;
        }

        return ! $this->excludedIdsFor($user)->contains($communityDua->id);
    }

    /**
     * @return Collection<int, int>
     */
    private function excludedIdsFor(User $user): Collection
    {
        $completed = $user->communityDuaCompletions()->pluck('community_dua_id');
        $skipped = $user->communityDuaSkips()->pluck('community_dua_id');

        return $completed->merge($skipped)->unique()->values();
    }

    private function pickNextDua(User $user, CommunityDuaQueueState $state): ?CommunityDua
    {
        $excluded = $this->excludedIdsFor($user);

        $eligible = CommunityDua::query()
            ->queueEligible()
            ->when($excluded->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $excluded))
            ->orderBy('id')
            ->get();

        if ($eligible->isEmpty()) {
            return null;
        }

        $paidDuas = $eligible->where('type', CommunityDuaType::Paid)->pluck('id')->all();
        $freeDuas = $eligible->where('type', CommunityDuaType::Free)->pluck('id')->all();
        $seenIds = $excluded->all();

        $showingType = $state->showing_type ?: 'paid';
        $pattern = (int) $state->pattern;
        $nextId = null;

        if ($showingType === 'paid' && $paidDuas !== []) {
            $pattern++;
            $nextId = $this->nextDuaIdOfType($paidDuas, $seenIds);

            if ($pattern === 2) {
                $showingType = 'free';
                $pattern = 0;
            }
        } else {
            $pattern++;
            $nextId = $this->nextDuaIdOfType($freeDuas, $seenIds);

            if ($pattern === 4) {
                $showingType = 'paid';
                $pattern = 0;
            }
        }

        if ($nextId === null) {
            $nextId = $eligible->first()?->id;
        }

        $state->forceFill([
            'showing_type' => $showingType,
            'pattern' => $pattern,
        ])->save();

        return $nextId ? CommunityDua::query()->find($nextId) : null;
    }

    /**
     * @param  list<int>  $duaIds
     * @param  list<int>  $seenIds
     */
    private function nextDuaIdOfType(array $duaIds, array $seenIds): ?int
    {
        foreach ($duaIds as $duaId) {
            if (! in_array($duaId, $seenIds, true)) {
                return $duaId;
            }
        }

        return null;
    }
}
