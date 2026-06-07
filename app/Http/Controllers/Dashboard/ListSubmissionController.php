<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Lists\Services\DuaListQueryService;
use App\Domains\Submissions\Actions\ReportDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Enums\DuaSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\ReportSubmissionRequest;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListSubmissionController extends Controller
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    public function index(
        Request $request,
        DuaList $duaList,
        DuaListQueryService $lists,
        DuaSubmissionQueryService $submissions,
    ): View {
        Gate::authorize('viewAny', [DuaSubmission::class, $duaList]);
        $user = Auth::user();
        $duaList = $lists->findOwnedForUser($user, $duaList->id);

        $status = $request->string('status')->toString() ?: DuaSubmissionStatus::Pending->value;
        $search = trim($request->string('search')->toString());
        $paginatedSubmissions = $submissions->paginateForList($duaList, [
            'status' => $status,
            'search' => $search,
        ], 15);
        $hasPremium = $this->entitlements->hasPremium($user);

        return view('dashboard.lists.submissions', [
            'user' => $user,
            'duaList' => $duaList,
            'submissions' => $paginatedSubmissions,
            'currentStatus' => $status,
            'search' => $search,
            'hasPremium' => $hasPremium,
            'lockedSubmissionCount' => $this->entitlements->lockedSubmissionCount($user, $duaList),
            'visibleSubmissionLimit' => $this->entitlements->visibleSubmissionLimit($user, $duaList),
            'visibleSubmissionIds' => $hasPremium
                ? $paginatedSubmissions->getCollection()->pluck('id')->all()
                : $this->entitlements->visibleSubmissionIds($user, $duaList),
            'statusCounts' => $submissions->statusCounts($duaList),
        ]);
    }

    public function complete(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Completed);

        return back()->with('status', 'Dua marked as completed.');
    }

    public function undo(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Pending);

        return back()->with('status', 'Dua moved back to incomplete.');
    }

    public function hide(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Hidden);

        return back()->with('status', 'Dua hidden.');
    }

    public function unhide(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Pending);

        return back()->with('status', 'Dua restored to incomplete.');
    }

    public function archive(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Archived);

        return back()->with('status', 'Dua archived.');
    }

    public function report(
        ReportSubmissionRequest $request,
        DuaList $duaList,
        DuaSubmission $submission,
        ReportDuaSubmissionAction $action,
    ): RedirectResponse {
        $this->authorizeSubmission($duaList, $submission);

        $action($submission, $request->validated());

        return back()->with('status', 'Dua marked as reported.');
    }

    private function authorizeSubmission(DuaList $duaList, DuaSubmission $submission): void
    {
        Gate::authorize('view', $duaList);
        abort_unless($submission->dua_list_id === $duaList->id, 404);
        Gate::authorize('manage', $submission);
    }
}
