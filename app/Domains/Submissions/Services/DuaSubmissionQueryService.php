<?php

namespace App\Domains\Submissions\Services;

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\User;
use App\Services\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DuaSubmissionQueryService extends Service
{
    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function paginateForList(DuaList $duaList, array $filters, int $perPage): LengthAwarePaginator
    {
        $status = $filters['status'] ?? DuaSubmissionStatus::Pending->value;
        $search = trim($filters['search'] ?? '');

        $query = $duaList->submissions()->latest();

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
        return [
            DuaSubmissionStatus::Pending->value => $duaList->submissions()->status(DuaSubmissionStatus::Pending)->count(),
            DuaSubmissionStatus::Completed->value => $duaList->submissions()->status(DuaSubmissionStatus::Completed)->count(),
            DuaSubmissionStatus::Hidden->value => $duaList->submissions()->status(DuaSubmissionStatus::Hidden)->count(),
            DuaSubmissionStatus::Archived->value => $duaList->submissions()->status(DuaSubmissionStatus::Archived)->count(),
            DuaSubmissionStatus::Reported->value => $duaList->submissions()->status(DuaSubmissionStatus::Reported)->count(),
        ];
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
