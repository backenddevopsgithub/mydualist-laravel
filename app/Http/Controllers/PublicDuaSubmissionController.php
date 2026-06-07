<?php

namespace App\Http\Controllers;

use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Models\DuaList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublicDuaSubmissionController extends Controller
{
    public function store(Request $request, DuaList $duaList, CreateDuaSubmissionAction $action): RedirectResponse
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
            'content' => ['required', 'string', 'min:3', 'max:1500'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $action($duaList, $data, $request->user());

        return redirect()
            ->route('dua-lists.public', $duaList)
            ->with('submission_status', 'Your dua request has been submitted.');
    }
}
