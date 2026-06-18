<?php

namespace App\Jobs;

use App\Enums\AdminExportStatus;
use App\Models\AdminExport;
use App\Services\AdminExportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateAdminExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public AdminExport $export,
    ) {}

    public function handle(AdminExportService $exports): void
    {
        $exports->generate($this->export->fresh());
    }

    public function failed(?Throwable $exception): void
    {
        $export = $this->export->fresh();

        if ($export === null) {
            return;
        }

        if (in_array($export->status, [AdminExportStatus::Pending, AdminExportStatus::Processing], true)) {
            app(AdminExportService::class)->recordFailure(
                $export,
                $exception ?? new \RuntimeException('Export job failed without an exception.'),
            );
        }
    }
}
