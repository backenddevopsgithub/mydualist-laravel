<?php

namespace App\Http\Controllers;

use App\Domains\Lists\Services\DuaListQueryService;
use App\Models\DuaList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DuaListQueryService $lists): View
    {
        $user = Auth::user();
        $status = $request->routeIs('dashboard.archived') ? DuaList::STATUS_ARCHIVED : DuaList::STATUS_ACTIVE;
        $summary = $lists->dashboardSummary($user);

        return view('dashboard.index', [
            'user' => $user,
            'duaLists' => $lists->paginateForUser($user, $status, 10),
            'currentStatus' => $status,
            'activeListsCount' => $summary['active_lists_count'],
            'archivedListsCount' => $summary['archived_lists_count'],
            'totalSubmissionsCount' => $summary['total_submissions_count'],
            'completedDuasCount' => $summary['completed_duas_count'],
        ]);
    }
}
