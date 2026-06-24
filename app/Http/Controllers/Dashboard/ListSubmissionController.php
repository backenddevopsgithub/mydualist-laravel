<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Community\Services\CommunityDuaEligibilityService;
use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Domains\Submissions\Actions\CreatePersonalDuaAction;
use App\Domains\Submissions\Actions\ReportDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Enums\DuaSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Submissions\ReportSubmissionRequest;
use App\Http\Requests\Submissions\StorePersonalDuaRequest;
use App\Http\Resources\Api\V1\Submissions\DuaSubmissionResource;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Support\Http\PartialHtmlRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ListSubmissionController extends Controller
{
    private const SUBMISSIONS_PER_PAGE = 20;

    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    public function index(
        Request $request,
        DuaList $duaList,
        DuaSubmissionQueryService $submissions,
        CommunityDuaEligibilityService $communityEligibility,
        CommunityDuaQueueService $communityQueue,
    ): View|Response {
        Gate::authorize('viewAny', [DuaSubmission::class, $duaList]);
        $user = Auth::user();

        $allowedStatuses = [
            DuaSubmissionStatus::Pending->value,
            DuaSubmissionStatus::Completed->value,
            DuaSubmissionStatus::Hidden->value,
        ];
        $status = $request->string('status')->toString() ?: DuaSubmissionStatus::Pending->value;
        $status = in_array($status, $allowedStatuses, true) ? $status : DuaSubmissionStatus::Pending->value;
        $paginatedSubmissions = $submissions->paginateForList($duaList, [
            'status' => $status,
            'search' => '',
        ], self::SUBMISSIONS_PER_PAGE, $user);
        $hasPremium = $this->entitlements->hasPremium($user);

        $showCommunityDuas = $communityEligibility->shouldShowForList($user, $duaList)
            && $status === DuaSubmissionStatus::Pending->value;
        $communityDua = $showCommunityDuas ? $communityQueue->resolveForUser($user) : null;

        $viewData = [
            'user' => $user,
            'duaList' => $duaList,
            'submissions' => $paginatedSubmissions,
            'currentStatus' => $status,
            'search' => '',
            'hasPremium' => $hasPremium,
            'lockedSubmissionCount' => $this->entitlements->lockedSubmissionCount($user, $duaList),
            'visibleSubmissionLimit' => $this->entitlements->visibleSubmissionLimit($user, $duaList),
            'statusCounts' => $submissions->statusCounts($duaList),
            'showCommunityDuas' => $showCommunityDuas,
            'communityDua' => $communityDua,
            'nextSubmissionPageUrl' => $this->relativeSubmissionPageUrl(
                $paginatedSubmissions->hasMorePages() ? $paginatedSubmissions->nextPageUrl() : null,
            ),
        ];

        if ($request->header('X-List-Submissions-Partial') === '1') {
            return view('dashboard.lists.partials.submission-cards', $viewData);
        }

        if (PartialHtmlRequest::wants($request)) {
            return response()
                ->view('dashboard.lists.partials.submission-card-items', $viewData)
                ->withHeaders($this->infiniteScrollHeaders($paginatedSubmissions));
        }

        return view('dashboard.lists.submissions', $viewData);
    }

    public function storePersonalDua(
        StorePersonalDuaRequest $request,
        DuaList $duaList,
        CreatePersonalDuaAction $action,
    ): RedirectResponse {
        Gate::authorize('view', $duaList);
        $user = Auth::user();

        $action($duaList, $user, $request->validated('content'));

        return back()->with('status', 'Personal dua added.');
    }

    public function complete(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse|JsonResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Completed);

        return $this->statusResponse($submission->fresh(), 'Dua marked as completed.');
    }

    public function undo(DuaList $duaList, DuaSubmission $submission, TransitionDuaSubmissionStatusAction $action): RedirectResponse|JsonResponse
    {
        $this->authorizeSubmission($duaList, $submission);
        $action($submission, DuaSubmissionStatus::Pending);

        return $this->statusResponse($submission->fresh(), 'Dua moved back to incomplete.');
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
        $submission->loadMissing('duaList');
        Gate::authorize('manage', $submission);
    }

    /**
     * @return array<string, string>
     */
    private function infiniteScrollHeaders($paginatedSubmissions): array
    {
        return [
            'X-Infinite-Scroll-Has-More' => $paginatedSubmissions->hasMorePages() ? 'true' : 'false',
            'X-Infinite-Scroll-Next-Page' => $this->relativeSubmissionPageUrl($paginatedSubmissions->nextPageUrl()),
            'X-Infinite-Scroll-Page' => (string) $paginatedSubmissions->currentPage(),
        ];
    }

    private function relativeSubmissionPageUrl(?string $url): string
    {
        if ($url === null || $url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($path) || $path === '') {
            return '';
        }

        return $query ? "{$path}?{$query}" : $path;
    }

    private function statusResponse(DuaSubmission $submission, string $message): RedirectResponse|JsonResponse
    {
        if (request()->expectsJson() || request()->ajax()) {
            $submission->loadMissing('duaList');

            return response()->json([
                'message' => $message,
                'data' => (new DuaSubmissionResource($submission))->resolve(),
                'meta' => [
                    'status_counts' => app(DuaSubmissionQueryService::class)->statusCounts($submission->duaList),
                ],
            ]);
        }

        return back()->with('status', $message);
    }
}
