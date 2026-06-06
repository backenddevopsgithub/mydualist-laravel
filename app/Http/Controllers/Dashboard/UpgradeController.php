<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UpgradeController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();

        return view('dashboard.upgrade', [
            'user' => $user,
            'currentPlan' => 'Free',
            'activeListsCount' => $user->duaLists()->active()->count(),
            'listLimit' => 3,
        ]);
    }
}
