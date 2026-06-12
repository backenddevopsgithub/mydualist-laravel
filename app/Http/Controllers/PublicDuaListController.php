<?php

namespace App\Http\Controllers;

use App\Domains\Lists\Services\DuaListQueryService;
use Illuminate\Http\Response;

class PublicDuaListController extends Controller
{
    public function show(string $duaList, DuaListQueryService $lists): Response
    {
        $duaList = $lists->findPublicBySlug($duaList);

        return response()->view('dashboard.show-list', [
            'duaList' => $duaList,
            'acceptsSubmissions' => $duaList->acceptsSubmissions(),
            'closedReason' => $duaList->closedReason(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
