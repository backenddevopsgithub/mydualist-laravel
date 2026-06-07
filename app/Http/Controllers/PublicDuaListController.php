<?php

namespace App\Http\Controllers;

use App\Models\DuaList;
use App\Enums\DuaSubmissionStatus;
use Illuminate\View\View;

class PublicDuaListController extends Controller
{
    public function show(DuaList $duaList): View
    {
        $duaList->load('user')
            ->loadCount([
                'submissions as submissions_count',
                'submissions as completed_submissions_count' => fn ($query) => $query->where('status', DuaSubmissionStatus::Completed->value),
            ]);

        return view('dashboard.show-list', [
            'duaList' => $duaList,
            'acceptsSubmissions' => $duaList->acceptsSubmissions(),
            'closedReason' => $duaList->closedReason(),
        ]);
    }
}
