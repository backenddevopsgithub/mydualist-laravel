<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\ResetPasswordAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.reset-password', [
            'email' => $request->query('email', ''),
            'token' => $request->query('token', ''),
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): RedirectResponse
    {
        try {
            $resetPassword($request->validated());
        } catch (DomainException $exception) {
            return back()
                ->withErrors(['email' => $exception->getMessage()])
                ->withInput($request->only('email', 'token'));
        }

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }
}
