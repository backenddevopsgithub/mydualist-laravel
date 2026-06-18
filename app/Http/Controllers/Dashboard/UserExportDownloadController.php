<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AdminExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportDownloadController extends Controller
{
    public function __invoke(Request $request, AdminExport $export): StreamedResponse
    {
        abort_unless($export->type->isUserFacing(), 404);

        Gate::authorize('download', $export);

        abort_unless($export->isReady(), 404);

        return Storage::disk('local')->download(
            (string) $export->file_path,
            $export->file_name ?? 'export.csv',
        );
    }
}
