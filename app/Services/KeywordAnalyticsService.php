<?php

namespace App\Services;

use App\Jobs\ComputeKeywordAnalyticsJob;
use App\Models\DuaSubmission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KeywordAnalyticsService extends Service
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function cacheKey(array $filters): string
    {
        return app(AnalyticsCacheService::class)->key('analytics.keywords', $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function computingLockKey(array $filters): string
    {
        return app(AnalyticsCacheService::class)->key('analytics.keywords.computing', $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{keyword: string, occurrences: int}>
     */
    public function readPrecomputed(array $filters = [], int $limit = 500): Collection
    {
        $cached = Cache::get($this->cacheKey($filters));

        if (! is_array($cached)) {
            $this->dispatchComputation($filters);

            return collect();
        }

        return collect($cached)
            ->take($limit)
            ->map(fn (array $row): object => (object) [
                'keyword' => (string) $row['keyword'],
                'occurrences' => (int) $row['occurrences'],
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function dispatchComputation(array $filters): void
    {
        if (! Cache::add($this->computingLockKey($filters), true, 600)) {
            return;
        }

        ComputeKeywordAnalyticsJob::dispatch($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, object{keyword: string, occurrences: int}>
     */
    public function aggregate(array $filters = []): Collection
    {
        $query = DuaSubmission::query()->select('content');
        $this->applyDateRange($query, $filters, 'created_at');

        $counts = [];

        $query->chunk(500, function ($submissions) use (&$counts): void {
            foreach ($submissions as $submission) {
                foreach ($this->extractKeywords((string) $submission->content) as $word) {
                    $counts[$word] = ($counts[$word] ?? 0) + 1;
                }
            }
        });

        arsort($counts);

        return collect($counts)
            ->take(500)
            ->map(fn (int $occurrences, string $keyword): object => (object) [
                'keyword' => $keyword,
                'occurrences' => $occurrences,
            ])
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  Collection<int, object{keyword: string, occurrences: int}>  $keywords
     */
    public function storePrecomputed(array $filters, Collection $keywords): void
    {
        Cache::put(
            $this->cacheKey($filters),
            $keywords
                ->map(fn (object $row): array => [
                    'keyword' => $row->keyword,
                    'occurrences' => $row->occurrences,
                ])
                ->values()
                ->all(),
            3600,
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function releaseComputationLock(array $filters): void
    {
        Cache::forget($this->computingLockKey($filters));
    }

    /**
     * @return list<string>
     */
    public function extractKeywords(string $content): array
    {
        $content = mb_strtolower($content);
        $words = preg_split('/[^\p{L}\p{N}]+/u', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $stopWords = $this->stopWords();

        return array_values(array_filter($words, function (string $word) use ($stopWords): bool {
            if (mb_strlen($word) < 3) {
                return false;
            }

            return ! in_array($word, $stopWords, true);
        }));
    }

    /**
     * @return list<string>
     */
    private function stopWords(): array
    {
        return [
            'the', 'and', 'for', 'you', 'your', 'may', 'allah', 'with', 'from', 'that',
            'this', 'have', 'has', 'are', 'was', 'were', 'been', 'will', 'our', 'his',
            'her', 'she', 'him', 'they', 'them', 'who', 'what', 'when', 'where', 'how',
            'all', 'but', 'not', 'can', 'could', 'would', 'should', 'about', 'into',
        ];
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
}
