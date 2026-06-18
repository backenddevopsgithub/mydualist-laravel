<?php

namespace App\Services;

use App\Enums\AdminExportStatus;
use App\Models\AdminExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdminExportMonitorService extends Service
{
    public function __construct(
        private readonly AdminExportService $exports,
    ) {}

    /**
     * @return Collection<int, AdminExport>
     */
    public function markStuckExportsAsFailed(): Collection
    {
        $pendingTimeout = (int) config('mydualist.admin_exports.pending_timeout_minutes', 15);
        $processingTimeout = (int) config('mydualist.admin_exports.processing_timeout_minutes', 35);

        $stuckExports = AdminExport::query()
            ->where(function ($query) use ($pendingTimeout, $processingTimeout): void {
                $query
                    ->where(function ($pending) use ($pendingTimeout): void {
                        $pending
                            ->where('status', AdminExportStatus::Pending)
                            ->where('created_at', '<', now()->subMinutes($pendingTimeout));
                    })
                    ->orWhere(function ($processing) use ($processingTimeout): void {
                        $processing
                            ->where('status', AdminExportStatus::Processing)
                            ->where('updated_at', '<', now()->subMinutes($processingTimeout));
                    });
            })
            ->get();

        foreach ($stuckExports as $export) {
            $previousStatus = $export->status;

            $message = $previousStatus === AdminExportStatus::Pending
                ? 'Export remained pending beyond the allowed queue wait time.'
                : 'Export remained processing beyond the allowed execution time.';

            $export->update([
                'status' => AdminExportStatus::Failed,
                'error_message' => $message,
            ]);

            $this->exports->notifyExportFailed($export, $message);

            Log::warning('Admin export marked as failed after timing out.', [
                'export_id' => $export->id,
                'user_id' => $export->user_id,
                'type' => $export->type?->value,
                'previous_status' => $previousStatus->value,
            ]);
        }

        return $stuckExports;
    }
}
