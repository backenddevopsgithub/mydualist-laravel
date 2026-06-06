<?php

namespace App\Http\Controllers;

use App\Models\DuaList;
use Illuminate\View\View;

class PublicDuaListController extends Controller
{
    public function show(DuaList $duaList): View
    {
        $duaList->load('user')
            ->loadCount([
                'submissions as submissions_count',
                'submissions as completed_submissions_count' => fn ($query) => $query->where('status', 'completed'),
            ]);

        return view('dashboard.show-list', [
            'duaList' => $duaList,
        ]);
    }
}
