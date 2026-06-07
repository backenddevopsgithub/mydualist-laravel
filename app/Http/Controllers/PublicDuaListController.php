<?php

namespace App\Http\Controllers;

use App\Domains\Lists\Services\DuaListQueryService;
use Illuminate\View\View;

class PublicDuaListController extends Controller
{
    public function show(string $duaList, DuaListQueryService $lists): View
    {
        $duaList = $lists->findPublicBySlug($duaList);

        return view('dashboard.show-list', [
            'duaList' => $duaList,
            'acceptsSubmissions' => $duaList->acceptsSubmissions(),
            'closedReason' => $duaList->closedReason(),
        ]);
    }
}
