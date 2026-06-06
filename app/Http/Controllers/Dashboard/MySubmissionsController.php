<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MySubmissionsController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return view('dashboard.submissions', [
            'user' => $user,
            'submissions' => $user->duaSubmissions()
                ->with('duaList.user')
                ->latest()
                ->paginate(12),
        ]);
    }
}
