<?php

namespace App\Http\Controllers\Admin;

use App\Models\MediaLibraryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminMediaServeController
{
    public function __invoke(Request $request, Media $media, string $conversion = ''): BinaryFileResponse
    {
        Gate::authorize('viewAny', MediaLibraryItem::class);

        if ($media->model_type !== MediaLibraryItem::class) {
            abort(404);
        }

        $conversionName = $conversion !== '' ? $conversion : null;
        $path = $conversionName
            ? $media->getPath($conversionName)
            : $media->getPath();

        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $media->mime_type,
            'Content-Disposition' => 'inline; filename="'.$media->file_name.'"',
        ]);
    }
}
