<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Submissions\Actions\DeleteDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ListSubmissionController extends Controller
{
    public function index(Request $request, DuaList $duaList): View
    {
        $this->authorizeOwner($duaList);

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

        return view('dashboard.lists.submissions', [
            'user' => Auth::user(),
            'duaList' => $duaList->loadCount([
                'submissions as submissions_count',
                'submissions as completed_submissions_count' => fn ($query) => $query->where('status', DuaSubmissionStatus::Completed->value),
            ]),
            'submissions' => $query->paginate(15)->withQueryString(),
            'currentStatus' => $status,
            'search' => $search,
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

        return back()->with('status', 'Dua moved back to pending.');
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

        return back()->with('status', 'Dua restored to pending.');
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
        $action($submission, DuaSubmissionStatus::Reported);

        return back()->with('status', 'Dua marked as reported.');
    }

    public function destroy(DuaList $duaList, DuaSubmission $submission, DeleteDuaSubmissionAction $action): RedirectResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission);

        return back()->with('status', 'Dua deleted.');
    }

    private function authorizeSubmission(DuaList $duaList, DuaSubmission $submission): void
    {
        $this->authorizeOwner($duaList);
        abort_unless($submission->dua_list_id === $duaList->id, 404);
    }

    private function authorizeOwner(DuaList $duaList): void
    {
        abort_unless($duaList->user_id === Auth::id(), 403);
    }
}
