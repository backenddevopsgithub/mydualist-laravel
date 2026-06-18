<?php

namespace App\Http\Controllers\Admin;

use App\Models\AdminExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminExportDownloadController
{
    public function __invoke(Request $request, AdminExport $export): StreamedResponse
    {
        Gate::authorize('download', $export);

        abort_unless($export->isReady(), 404);

        return Storage::disk('local')->download(
            (string) $export->file_path,
            $export->file_name ?? 'export.csv',
        );
    }
}
