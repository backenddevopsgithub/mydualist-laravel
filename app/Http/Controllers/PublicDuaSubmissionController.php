<?php

namespace App\Http\Controllers;

use App\Domains\Cms\Services\DuaSuggestionService;
use App\Domains\Security\Services\PublicSubmissionSpamGuard;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Http\Requests\Submissions\StorePublicSubmissionRequest;
use App\Models\DuaList;
use App\Support\PublicSubmissionIdempotency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PublicDuaSubmissionController extends Controller
{
    public function store(
        StorePublicSubmissionRequest $request,
        DuaList $duaList,
        CreateDuaSubmissionAction $action,
        PublicSubmissionSpamGuard $spamGuard,
        DuaSuggestionService $suggestions,
    ): RedirectResponse {
        if (! $duaList->acceptsSubmissions()) {
            throw ValidationException::withMessages([
                'content' => $duaList->closedReason() ?? 'This list is not accepting submissions.',
            ]);
        }

        $data = $request->validated();
        $batchKey = (string) ($data['submission_batch_key'] ?? '');

        if ($batchKey !== '') {
            $existingCount = PublicSubmissionIdempotency::findExistingCount($batchKey, $duaList);

            if ($existingCount !== null) {
                return redirect()
                    ->to(route('cms.show', $duaList).'#submit-dua')
                    ->with('submission_status', $existingCount === 1
                        ? 'Your dua request has been submitted.'
                        : "Your {$existingCount} dua requests have been submitted.");
            }
        }

        $spamGuard->inspect($duaList, $data, $request->ip());

        $submissions = $action($duaList, $data, $request->user());
        $suggestions->incrementUsedCounts($data['suggestion_ids'] ?? []);
        $count = $submissions->count();

        if ($batchKey !== '') {
            PublicSubmissionIdempotency::remember($batchKey, $duaList, $count);
        }

        return redirect()
            ->to(route('cms.show', $duaList).'#submit-dua')
            ->with('submission_status', $count === 1
                ? 'Your dua request has been submitted.'
                : "Your {$count} dua requests have been submitted.");
    }
}
