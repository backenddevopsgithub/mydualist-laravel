<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Community\Actions\CompleteCommunityDuaAction;
use App\Domains\Community\Actions\ReportCommunityDuaAction;
use App\Domains\Community\Actions\SkipCommunityDuaAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\ReportSubmissionRequest;
use App\Models\CommunityDua;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class CommunityDuaController extends Controller
{
    public function complete(
        DuaList $duaList,
        CommunityDua $communityDua,
        CompleteCommunityDuaAction $action,
    ): RedirectResponse {
        Gate::authorize('view', $duaList);
        abort_unless($duaList->user_id === Auth::id(), 403);

        try {
            $action($communityDua, Auth::user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['community_dua' => $exception->getMessage()]);
        }

        return back()->with('status', 'Community dua marked as completed.');
    }

    public function skip(
        DuaList $duaList,
        CommunityDua $communityDua,
        SkipCommunityDuaAction $action,
    ): RedirectResponse {
        Gate::authorize('view', $duaList);
        abort_unless($duaList->user_id === Auth::id(), 403);

        $action($communityDua, Auth::user());

        return back()->with('status', 'Showing the next community dua.');
    }

    public function report(
        ReportSubmissionRequest $request,
        DuaList $duaList,
        CommunityDua $communityDua,
        ReportCommunityDuaAction $action,
    ): RedirectResponse {
        Gate::authorize('view', $duaList);
        abort_unless($duaList->user_id === Auth::id(), 403);

        $action($communityDua, $request->validated());

        return back()->with('status', 'Community dua reported.');
    }
}
