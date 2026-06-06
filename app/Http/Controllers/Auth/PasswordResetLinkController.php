<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\SendPasswordResetLinkAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request, SendPasswordResetLinkAction $sendPasswordResetLink): RedirectResponse
    {
        $sendPasswordResetLink($request->validated());

        return back()->with('status', 'If an account exists for that email, a reset link has been sent.');
    }
}
