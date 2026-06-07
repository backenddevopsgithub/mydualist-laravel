<?php

namespace App\Http\Controllers\Dashboard;

use App\Domains\Support\Actions\CreateSupportTicketAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Support\SupportTicketReasons;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function create(): View
    {
        return view('dashboard.support', [
            'user' => Auth::user(),
            'reasons' => SupportTicketReasons::labels(),
        ]);
    }

    public function store(StoreSupportTicketRequest $request, CreateSupportTicketAction $action): RedirectResponse
    {
        ($action)(
            $request->user(),
            $request->safe()->except('image'),
            $request->file('image'),
        );

        return redirect()
            ->route('dashboard.support')
            ->with('status', 'Thanks. Your support request has been sent.');
    }
}
