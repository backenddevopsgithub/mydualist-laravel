<?php

namespace App\Domains\Submissions\Services;

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DuaSubmissionQueryService extends Service
{
    public function __construct(
        private readonly DuaSubmissionOrderingService $ordering,
    ) {}

    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function paginateForList(DuaList $duaList, array $filters, int $perPage, ?User $owner = null): LengthAwarePaginator
    {
        $status = $filters['status'] ?? DuaSubmissionStatus::Pending->value;
        $search = trim($filters['search'] ?? '');
        $owner ??= $duaList->user;

        $query = DuaSubmission::query()->where('dua_list_id', $duaList->id);

        if ($owner !== null) {
            $this->ordering->applyOwnerListOrdering($query, $duaList, $owner);
        } else {
            $query->orderBy('id');
        }

        if (in_array($status, DuaSubmissionStatus::values(), true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search): void {
                $query
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(DuaList $duaList): array
    {
        return $duaList->fresh()->statusCountMap();
    }

    /**
     * @param  list<string>  $with
     */
    public function paginateForUser(User $user, int $perPage = 12, array $with = ['duaList.user']): LengthAwarePaginator
    {
        return $user->duaSubmissions()
            ->with($with)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
