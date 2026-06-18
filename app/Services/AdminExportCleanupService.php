<?php

namespace App\Services;

use App\Enums\AdminExportStatus;
use App\Models\AdminExport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AdminExportCleanupService extends Service
{
    public function pruneExpiredExports(): int
    {
        $retentionDays = (int) config('mydualist.admin_exports.retention_days', 7);
        $cutoff = now()->subDays($retentionDays);

        $pruned = 0;

        AdminExport::query()
            ->where(function ($query) use ($cutoff): void {
                $query
                    ->where(function ($completed) use ($cutoff): void {
                        $completed
                            ->where('status', AdminExportStatus::Completed)
                            ->where('completed_at', '<', $cutoff);
                    })
                    ->orWhere(function ($failed) use ($cutoff): void {
                        $failed
                            ->where('status', AdminExportStatus::Failed)
                            ->where('updated_at', '<', $cutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function (Collection $exports) use (&$pruned): void {
                foreach ($exports as $export) {
                    /** @var AdminExport $export */
                    $this->deleteExportFile($export);
                    $export->delete();
                    $pruned++;
                }
            });

        return $pruned;
    }

    public function deleteExportFile(AdminExport $export): void
    {
        if ($export->file_path === null) {
            return;
        }

        $disk = Storage::disk('local');

        if ($disk->exists($export->file_path)) {
            $disk->delete($export->file_path);
        }
    }
}
