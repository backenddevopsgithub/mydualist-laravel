<?php

namespace App\Console\Commands;

use App\Services\AdminExportCleanupService;
use Illuminate\Console\Command;

class CleanupAdminExportsCommand extends Command
{
    protected $signature = 'admin:cleanup-exports';

    protected $description = 'Delete expired admin export files and records';

    public function handle(AdminExportCleanupService $cleanup): int
    {
        $pruned = $cleanup->pruneExpiredExports();

        $this->info('Removed '.$pruned.' expired admin export(s).');

        return self::SUCCESS;
    }
}
