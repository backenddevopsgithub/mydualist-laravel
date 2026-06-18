<?php

namespace App\Console\Commands;

use App\Services\AdminExportMonitorService;
use Illuminate\Console\Command;

class MonitorAdminExportsCommand extends Command
{
    protected $signature = 'admin:monitor-exports';

    protected $description = 'Mark admin exports stuck in pending or processing as failed';

    public function handle(AdminExportMonitorService $monitor): int
    {
        $stuckExports = $monitor->markStuckExportsAsFailed();

        $this->info('Marked '.$stuckExports->count().' stuck admin export(s) as failed.');

        return self::SUCCESS;
    }
}
