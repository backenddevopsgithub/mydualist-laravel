<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Http\Controllers\Controller;
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
    ) {
    }

    public function index(Request $request, DuaList $duaList): View
    {
        Gate::authorize('viewAny', [DuaSubmission::class, $duaList]);
        $user = Auth::user();

        $status = $request->string('status')->toString() ?: DuaSubmissionStatus::Pending->value;
        $search = trim($request->string('search')->toString());

        $query = $duaList->submissions()
            ->latest()
            ->when(in_array($status, DuaSubmissionStatus::values(), true), fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            });

        $paginatedSubmissions = $query->paginate(15)->withQueryString();
        $hasPremium = $this->entitlements->hasPremium($user);

        return view('dashboard.lists.submissions', [
            'user' => $user,
            'duaList' => $duaList->loadCount([
                'submissions as submissions_count',
                'submissions as completed_submissions_count' => fn ($query) => $query->where('status', DuaSubmissionStatus::Completed->value),
            ]),
            'submissions' => $paginatedSubmissions,
            'currentStatus' => $status,
            'search' => $search,
            'hasPremium' => $hasPremium,
            'lockedSubmissionCount' => $this->entitlements->lockedSubmissionCount($user, $duaList),
            'visibleSubmissionLimit' => $this->entitlements->visibleSubmissionLimit($user, $duaList),
            'visibleSubmissionIds' => $hasPremium
                ? $paginatedSubmissions->getCollection()->pluck('id')->all()
                : $this->entitlements->visibleSubmissionIds($user, $duaList),
            'statusCounts' => [
                DuaSubmissionStatus::Pending->value => $duaList->submissions()->status(DuaSubmissionStatus::Pending)->count(),
                DuaSubmissionStatus::Completed->value => $duaList->submissions()->status(DuaSubmissionStatus::Completed)->count(),
                DuaSubmissionStatus::Hidden->value => $duaList->submissions()->status(DuaSubmissionStatus::Hidden)->count(),
                DuaSubmissionStatus::Archived->value => $duaList->submissions()->status(DuaSubmissionStatus::Archived)->count(),
                DuaSubmissionStatus::Reported->value => $duaList->submissions()->status(DuaSubmissionStatus::Reported)->count(),
            ],
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

    public function report(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);

        $data = request()->validate([
            'report_reason' => ['required', 'string', 'in:spam,offensive,duplicate,irrelevant,other'],
            'report_note' => ['nullable', 'required_if:report_reason,other', 'string', 'max:1000'],
        ]);

        $action($submission, DuaSubmissionStatus::Reported);
        $submission->forceFill([
            'report_reason' => $data['report_reason'],
            'report_note' => $data['report_note'] ?? null,
        ])->save();

        return back()->with('status', 'Dua marked as reported.');
    }

    private function authorizeSubmission(DuaList $duaList, DuaSubmission $submission): void
    {
        Gate::authorize('view', $duaList);
        abort_unless($submission->dua_list_id === $duaList->id, 404);
        Gate::authorize('manage', $submission);
    }
}
