<?php

namespace App\Jobs;

use App\Services\KeywordAnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ComputeKeywordAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public array $filters,
    ) {}

    public function handle(KeywordAnalyticsService $keywords): void
    {
        try {
            $keywords->storePrecomputed($this->filters, $keywords->aggregate($this->filters));
        } finally {
            $keywords->releaseComputationLock($this->filters);
        }
    }
}
