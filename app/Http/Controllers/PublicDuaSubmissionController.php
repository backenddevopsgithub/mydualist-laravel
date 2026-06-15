<?php

namespace App\Http\Controllers;

use App\Domains\Security\Services\PublicSubmissionSpamGuard;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Http\Requests\Submissions\StorePublicSubmissionRequest;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class PublicDuaSubmissionController extends Controller
{
    public function store(
        StorePublicSubmissionRequest $request,
        DuaList $duaList,
        CreateDuaSubmissionAction $action,
        PublicSubmissionSpamGuard $spamGuard,
    ): RedirectResponse {
        if (! $duaList->acceptsSubmissions()) {
            throw ValidationException::withMessages([
                'content' => $duaList->closedReason() ?? 'This list is not accepting submissions.',
            ]);
        }

        $data = $request->validated();

        $spamGuard->inspect($duaList, $data, $request->ip());

        $submissions = $action($duaList, $data, $request->user());
        $count = $submissions->count();

        return redirect()
            ->to(route('cms.show', $duaList).'#submit-dua')
            ->with('submission_status', $count === 1
                ? 'Your dua request has been submitted.'
                : "Your {$count} dua requests have been submitted.");
    }
}
