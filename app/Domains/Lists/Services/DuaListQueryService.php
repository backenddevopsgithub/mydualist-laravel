<?php

namespace App\Domains\Lists\Services;

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class DuaListQueryService extends Service
{
    /**
     * @return array<int, string>
     */
    private function submissionCountRelations(): array
    {
        return [
            'submissions as submissions_count',
            'submissions as completed_submissions_count' => fn ($query) => $query->where('status', DuaSubmissionStatus::Completed->value),
        ];
    }

    public function paginateForUser(User $user, string $status, int $perPage): LengthAwarePaginator
    {
        return $user->duaLists()
            ->where('status', $status)
            ->withCount($this->submissionCountRelations())
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findOwnedForUser(User $user, int $listId): DuaList
    {
        return $user->duaLists()
            ->withCount($this->submissionCountRelations())
            ->whereKey($listId)
            ->firstOrFail();
    }

    public function findPublicBySlug(string $slug): DuaList
    {
        return DuaList::query()
            ->where('slug', $slug)
            ->with('user')
            ->withCount($this->submissionCountRelations())
            ->firstOrFail();
    }

    /**
     * @return Collection<int, DuaList>
     */
    public function listsForProfile(User $user): Collection
    {
        return $user->duaLists()->latest()->get();
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
        $ownedListIds = $user->duaLists()->pluck('id');

        return [
            'active_lists_count' => $user->duaLists()->active()->count(),
            'archived_lists_count' => $user->duaLists()->archived()->count(),
            'total_submissions_count' => DuaSubmission::query()->whereIn('dua_list_id', $ownedListIds)->count(),
            'completed_duas_count' => DuaSubmission::query()
                ->whereIn('dua_list_id', $ownedListIds)
                ->where('status', DuaSubmissionStatus::Completed->value)
                ->count(),
        ];
    }
}
