<?php

namespace App\Http\Controllers;

use App\Domains\Security\Services\PublicSubmissionSpamGuard;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicDuaSubmissionController extends Controller
{
    public function store(
        Request $request,
        DuaList $duaList,
        CreateDuaSubmissionAction $action,
        PublicSubmissionSpamGuard $spamGuard,
    ): RedirectResponse
    {
        if (! $duaList->acceptsSubmissions()) {
            throw ValidationException::withMessages([
                'content' => $duaList->closedReason() ?? 'This list is not accepting submissions.',
            ]);
        }

        $data = $request->validate([
            'first_name' => ['nullable', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_anonymous' => ['nullable', 'boolean'],
            'content' => ['nullable', 'required_without:duas', 'string', 'min:3', 'max:1500'],
            'duas' => ['nullable', 'array', 'min:1', 'max:35'],
            'duas.*' => ['required', 'string', 'min:3', 'max:1500'],
            'note' => ['nullable', 'string', 'max:500'],
            'website' => ['nullable', 'string', 'max:0'],
        ]);

        $spamGuard->inspect($request, $duaList, $data);

        $submissions = $action($duaList, $data, $request->user());
        $count = $submissions->count();

        return redirect()
            ->route('dua-lists.public', $duaList)
            ->with('submission_status', $count === 1
                ? 'Your dua request has been submitted.'
                : "Your {$count} dua requests have been submitted.");
    }
}
