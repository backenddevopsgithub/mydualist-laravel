<?php

namespace App\Services;

use App\Enums\DuaSubmissionStatus;
use App\Enums\UserRole;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Support\DuaListOccasions;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsQueryService extends Service
{
    public function __construct(
        private readonly AnalyticsCacheService $cache,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function duaListMetrics(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.dua_list_metrics', $filters);

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $listsQuery = $this->applyDateRange(DuaList::query(), $filters, 'created_at');
            $totalLists = (clone $listsQuery)->count();

            $submissionsQuery = DuaSubmission::query();
            $this->applyDateRange($submissionsQuery, $filters, 'created_at');

            if (isset($filters['category'])) {
                $submissionsQuery->whereHas('duaList', fn (Builder $q) => $q->where('occasion', $filters['category']));
            }

            if (isset($filters['creator_email'])) {
                $submissionsQuery->whereHas('duaList.user', fn (Builder $q) => $q->where('email', 'like', '%'.$filters['creator_email'].'%'));
            }

            $totalSubmissions = (clone $submissionsQuery)->count();
            $completedSubmissions = (clone $submissionsQuery)
                ->where('status', DuaSubmissionStatus::Completed)
                ->count();

            $completionRate = $totalSubmissions > 0
                ? round(($completedSubmissions / $totalSubmissions) * 100, 1)
                : 0.0;

            return [
                'total_lists' => $totalLists,
                'total_submissions' => $totalSubmissions,
                'completion_rate' => $completionRate,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DuaList>
     */
    public function duaListAnalyticsQuery(array $filters = []): Builder
    {
        $query = DuaList::query()
            ->with(['user:id,name,email']);

        $this->applyDateRange($query, $filters, 'created_at');

        if (! empty($filters['category'])) {
            $query->where('occasion', $filters['category']);
        }

        if (! empty($filters['creator_email'])) {
            $query->whereHas('user', fn (Builder $q) => $q->where('email', 'like', '%'.$filters['creator_email'].'%'));
        }

        return $query->latest('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function userMetrics(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.user_metrics', $filters);

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $usersQuery = $this->applyDateRange(User::query(), $filters, 'created_at');
            $totalUsers = (clone $usersQuery)->count();

            $listsQuery = $this->applyDateRange(DuaList::query(), $filters, 'created_at');
            $totalLists = (clone $listsQuery)->count();

            $avgListsPerUser = $totalUsers > 0
                ? round($totalLists / $totalUsers, 2)
                : 0.0;

            return [
                'total_users' => $totalUsers,
                'total_lists' => $totalLists,
                'avg_lists_per_user' => $avgListsPerUser,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    public function userAnalyticsQuery(array $filters = []): Builder
    {
        $query = User::query()
            ->withCount(['duaLists', 'duaSubmissions']);

        $this->applyDateRange($query, $filters, 'created_at');

        return $query->latest('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function uniqueUsersMetrics(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.unique_users_metrics', $filters);

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $query = User::query();
            $this->applyDateRange($query, $filters, 'created_at');

            if (isset($filters['verified'])) {
                if ($filters['verified'] === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($filters['verified'] === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            }

            $total = (clone $query)->count();
            $verified = (clone $query)->whereNotNull('email_verified_at')->count();

            return [
                'total_registered' => $total,
                'verified' => $verified,
                'unverified' => $total - $verified,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<User>
     */
    public function uniqueUsersQuery(array $filters = []): Builder
    {
        $query = User::query();
        $this->applyDateRange($query, $filters, 'created_at');

        if (! empty($filters['verified'])) {
            if ($filters['verified'] === 'verified') {
                $query->whereNotNull('email_verified_at');
            } elseif ($filters['verified'] === 'unverified') {
                $query->whereNull('email_verified_at');
            }
        }

        return $query->latest('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function categoryMetrics(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.category_metrics', $filters);

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $query = DuaList::query()->select('occasion', DB::raw('COUNT(*) as list_count'));
            $this->applyDateRange($query, $filters, 'created_at');

            $rows = $query->groupBy('occasion')->orderByDesc('list_count')->get();
            $totalLists = $rows->sum('list_count');

            return [
                'total_categories' => $rows->count(),
                'total_lists' => $totalLists,
                'top_categories' => $rows->take(10)->map(fn ($row) => [
                    'occasion' => $row->occasion,
                    'label' => DuaListOccasions::label((string) $row->occasion),
                    'list_count' => (int) $row->list_count,
                    'percentage' => $totalLists > 0
                        ? round(((int) $row->list_count / $totalLists) * 100, 1)
                        : 0.0,
                ])->values()->all(),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{occasion: string, list_count: int, percentage: float}>
     */
    public function categoryAnalyticsRows(array $filters = []): Collection
    {
        $query = DuaList::query()
            ->select('occasion', DB::raw('COUNT(*) as list_count'));

        $this->applyDateRange($query, $filters, 'created_at');

        $rows = $query->groupBy('occasion')->orderByDesc('list_count')->get();
        $totalLists = $rows->sum('list_count');

        return $rows->map(fn ($row) => (object) [
            'occasion' => (string) $row->occasion,
            'label' => DuaListOccasions::label((string) $row->occasion),
            'list_count' => (int) $row->list_count,
            'percentage' => $totalLists > 0
                ? round(((int) $row->list_count / $totalLists) * 100, 1)
                : 0.0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function categoryTrendData(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.category_trend', $filters);

        return Cache::remember($cacheKey, 600, function () use ($filters): array {
            $query = DuaList::query()
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'));

            $this->applyDateRange($query, $filters, 'created_at');

            $rows = $query->groupBy('date')->orderBy('date')->get();

            return [
                'labels' => $rows->pluck('date')->map(fn ($d) => (string) $d)->all(),
                'data' => $rows->pluck('count')->map(fn ($c) => (int) $c)->all(),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function submissionMetrics(array $filters = []): array
    {
        $cacheKey = $this->cache->key('analytics.submission_metrics', $filters);

        return Cache::remember($cacheKey, 300, function () use ($filters): array {
            $query = $this->submissionAnalyticsQuery($filters);

            $total = (clone $query)->count();
            $adminTest = (clone $query)->adminTest()->count();
            $uniqueSubmitters = (clone $query)->distinct('email')->count('email');

            return [
                'total_submissions' => $total,
                'admin_test_submissions' => $adminTest,
                'unique_submitters' => $uniqueSubmitters,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DuaSubmission>
     */
    public function submissionAnalyticsQuery(array $filters = []): Builder
    {
        $query = DuaSubmission::query()->with(['duaList:id,title']);

        $this->applyDateRange($query, $filters, 'created_at');

        if (! empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (! empty($filters['dua_list_id'])) {
            $query->where('dua_list_id', $filters['dua_list_id']);
        }

        if (empty($filters['include_admin_submissions'])) {
            $query->whereNotAdminTest();
        }

        return $query->latest('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function keywordMetrics(array $filters = []): array
    {
        $keywords = $this->keywordOccurrences($filters);

        return [
            'total_keywords' => $keywords->sum('occurrences'),
            'unique_keywords' => $keywords->count(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{keyword: string, occurrences: int}>
     */
    public function keywordOccurrences(array $filters = [], int $limit = 500): Collection
    {
        return app(KeywordAnalyticsService::class)->readPrecomputed($filters, $limit);
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<\Illuminate\Database\Eloquent\Model>
     */
    private function applyDateRange(Builder $query, array $filters, string $column): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->where($column, '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where($column, '<=', $filters['date_to'].' 23:59:59');
        }

        return $query;
    }

    public function adminEmails(): Collection
    {
        return Cache::remember($this->cache->key('analytics.admin_emails'), 3600, fn () => User::query()
            ->where('role', UserRole::Admin)
            ->pluck('email'));
    }
}
