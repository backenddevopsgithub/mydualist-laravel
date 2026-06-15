<?php

namespace App\Support\Http;

use Illuminate\Http\Request;

class PartialHtmlRequest
{
    public static function wants(Request $request): bool
    {
        if ($request->ajax()) {
            return true;
        }

        return str_contains($request->header('Accept', ''), 'text/html+partial');
    }
}
