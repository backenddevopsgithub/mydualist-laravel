<?php

namespace App\Http\Controllers;

use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = Auth::user();

        $status = $request->routeIs('dashboard.archived') ? DuaList::STATUS_ARCHIVED : DuaList::STATUS_ACTIVE;
        $duaLists = $user->duaLists()
            ->where('status', $status)
            ->withCount([
                'submissions as submissions_count',
                'submissions as completed_submissions_count' => fn ($query) => $query->where('status', DuaSubmission::STATUS_COMPLETED),
            ])
            ->latest()
            ->paginate(10);

        $ownedListIds = $user->duaLists()->pluck('id');

        return view('dashboard.index', [
            'user' => $user,
            'duaLists' => $duaLists,
            'currentStatus' => $status,
            'activeListsCount' => $user->duaLists()->active()->count(),
            'archivedListsCount' => $user->duaLists()->archived()->count(),
            'totalSubmissionsCount' => DuaSubmission::query()->whereIn('dua_list_id', $ownedListIds)->count(),
            'completedDuasCount' => DuaSubmission::query()->whereIn('dua_list_id', $ownedListIds)->where('status', DuaSubmission::STATUS_COMPLETED)->count(),
        ]);
    }
}
