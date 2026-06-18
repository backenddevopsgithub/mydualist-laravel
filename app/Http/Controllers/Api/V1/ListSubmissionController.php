<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Submissions\Actions\CreatePersonalDuaAction;
use App\Domains\Submissions\Actions\ReportDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Enums\DuaSubmissionStatus;
use App\Http\Requests\Api\V1\Submissions\IndexSubmissionRequest;
use App\Http\Requests\Submissions\ReportSubmissionRequest;
use App\Http\Requests\Submissions\StorePersonalDuaRequest;
use App\Http\Resources\Api\V1\Submissions\DuaSubmissionResource;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ListSubmissionController extends ApiController
{
    public function index(
        IndexSubmissionRequest $request,
        DuaList $duaList,
        DuaSubmissionQueryService $submissions,
        UserEntitlementService $entitlements,
    ): JsonResponse {
        abort_unless($duaList->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        Gate::authorize('viewAny', [DuaSubmission::class, $duaList]);

        $user = $request->user();

        $paginator = $submissions->paginateForList(
            $duaList,
            $request->filters(),
            $request->perPage(),
            $user,
        );

        $response = $this->paginated($paginator, DuaSubmissionResource::class, 'Submissions retrieved.');
        $payload = $response->getData(true);

        $payload['meta'] = [
            ...($payload['meta'] ?? []),
            'status_counts' => $submissions->statusCounts($duaList),
            'has_premium' => $entitlements->hasPremium($user),
            'visible_submission_limit' => $entitlements->visibleSubmissionLimit($user, $duaList),
            'locked_submission_count' => $entitlements->lockedSubmissionCount($user, $duaList),
        ];

        return response()->json($payload);
    }

    public function storePersonalDua(
        StorePersonalDuaRequest $request,
        DuaList $duaList,
        CreatePersonalDuaAction $action,
    ): JsonResponse {
        abort_unless($duaList->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        Gate::authorize('view', $duaList);

        $submission = $action($duaList, $request->user(), $request->validated('content'));

        return $this->success(
            (new DuaSubmissionResource($submission))->resolve(),
            'Personal dua added.',
            201,
        );
    }

    public function complete(Request $request, DuaList $duaList, int $submission, TransitionDuaSubmissionStatusAction $action): JsonResponse
    {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        $action($record, DuaSubmissionStatus::Completed);

        return $this->submissionResponse($record->fresh(), 'Dua marked as completed.');
    }

    public function undo(Request $request, DuaList $duaList, int $submission, TransitionDuaSubmissionStatusAction $action): JsonResponse
    {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        $action($record, DuaSubmissionStatus::Pending);

        return $this->submissionResponse($record->fresh(), 'Dua moved back to incomplete.');
    }

    public function hide(Request $request, DuaList $duaList, int $submission, TransitionDuaSubmissionStatusAction $action): JsonResponse
    {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        $action($record, DuaSubmissionStatus::Hidden);

        return $this->submissionResponse($record->fresh(), 'Dua hidden.');
    }

    public function unhide(Request $request, DuaList $duaList, int $submission, TransitionDuaSubmissionStatusAction $action): JsonResponse
    {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        $action($record, DuaSubmissionStatus::Pending);

        return $this->submissionResponse($record->fresh(), 'Dua restored to incomplete.');
    }

    public function archive(Request $request, DuaList $duaList, int $submission, TransitionDuaSubmissionStatusAction $action): JsonResponse
    {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        $action($record, DuaSubmissionStatus::Archived);

        return $this->submissionResponse($record->fresh(), 'Dua archived.');
    }

    public function report(
        ReportSubmissionRequest $request,
        DuaList $duaList,
        int $submission,
        ReportDuaSubmissionAction $action,
    ): JsonResponse {
        $record = $this->resolveSubmission($request, $duaList, $submission);

        Gate::authorize('report', $record);

        $updated = $action($record, $request->validated());

        return $this->submissionResponse($updated, 'Dua marked as reported.');
    }

    private function resolveSubmission(Request $request, DuaList $duaList, int $submissionId): DuaSubmission
    {
        abort_unless($duaList->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        Gate::authorize('view', $duaList);

        $submission = $duaList->submissions()->whereKey($submissionId)->firstOrFail();
        $submission->loadMissing('duaList');

        Gate::authorize('manage', $submission);

        return $submission;
    }

    private function submissionResponse(DuaSubmission $submission, string $message): JsonResponse
    {
        return $this->success(
            (new DuaSubmissionResource($submission))->resolve(),
            $message,
        );
    }
}
