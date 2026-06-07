<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Submissions\Services\DuaSubmissionQueryService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MySubmissionsController extends Controller
{
    public function __invoke(DuaSubmissionQueryService $submissions): View
    {
        $user = Auth::user();

        return view('dashboard.submissions', [
            'user' => $user,
            'submissions' => $submissions->paginateForUser($user),
        ]);
    }
}
