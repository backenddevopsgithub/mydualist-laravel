<?php

namespace App\Observers;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\AnalyticsCacheService;

class AnalyticsCacheInvalidationObserver
{
    public function __construct(
        private readonly AnalyticsCacheService $cache,
    ) {}

    public function created(User|DuaList|DuaSubmission $model): void
    {
        $this->cache->invalidate();
    }

    public function updated(User|DuaList|DuaSubmission $model): void
    {
        $this->cache->invalidate();
    }

    public function deleted(User|DuaList|DuaSubmission $model): void
    {
        $this->cache->invalidate();
    }

    public function restored(User|DuaList|DuaSubmission $model): void
    {
        $this->cache->invalidate();
    }
}
