<?php

namespace App\Domains\Lists\Services;

use App\Models\DuaList;
use App\Models\User;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DuaListQueryService extends Service
{
    public function paginateForUser(User $user, string $status, int $perPage): LengthAwarePaginator
    {
        return $user->duaLists()
            ->where('status', $status)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findOwnedForUser(User $user, int $listId): DuaList
    {
        return $user->duaLists()
            ->whereKey($listId)
            ->firstOrFail();
    }

    public function findPublicBySlug(string $slug): DuaList
    {
        return DuaList::query()
            ->where('slug', $slug)
            ->with('user')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, DuaList>
     */
    public function sidebarListsForUser(User $user): Collection
    {
        return $user->duaLists()
            ->where('status', DuaList::STATUS_ACTIVE)
            ->latest()
            ->get(['id', 'title', 'slug']);
    }

    /**
     * @return Collection<int, DuaList>
     */
    public function listsForProfile(User $user): Collection
    {
        return $user->duaLists()->latest()->get();
    }

    /**
     * @return Collection<int, DuaList>
     */
    public function creatorListsForProfile(User $user): Collection
    {
        return $user->duaLists()
            ->where('list_mode', \App\Support\CreatorMode::MODE_CREATOR)
            ->where('status', DuaList::STATUS_ACTIVE)
            ->latest()
            ->get();
    }

    /**
     * @return array{
     *     active_lists_count: int,
     *     archived_lists_count: int,
     *     total_submissions_count: int,
     *     completed_duas_count: int
     * }
     */
    public function dashboardSummary(User $user): array
    {
        $aggregates = $user->duaLists()
            ->selectRaw(
                'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_lists_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as archived_lists_count,
                COALESCE(SUM(submissions_count), 0) as total_submissions_count,
                COALESCE(SUM(completed_submissions_count), 0) as completed_duas_count',
                [DuaList::STATUS_ACTIVE, DuaList::STATUS_ARCHIVED],
            )
            ->first();

        return [
            'active_lists_count' => (int) ($aggregates->active_lists_count ?? 0),
            'archived_lists_count' => (int) ($aggregates->archived_lists_count ?? 0),
            'total_submissions_count' => (int) ($aggregates->total_submissions_count ?? 0),
            'completed_duas_count' => (int) ($aggregates->completed_duas_count ?? 0),
        ];
    }
}
