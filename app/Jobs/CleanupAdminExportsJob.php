<?php

namespace App\Jobs;

use App\Services\AdminExportCleanupService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAdminExportsJob implements ShouldQueue
{
    use Queueable;

    public function handle(AdminExportCleanupService $cleanup): void
    {
        $cleanup->pruneExpiredExports();
    }
}
