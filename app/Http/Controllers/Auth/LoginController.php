<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Actions\AuthenticateUserAction;
use App\Exceptions\ForbiddenException;
use App\Exceptions\UnauthorizedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticateUserAction $action): RedirectResponse
    {
        $credentials = $request->validated();

        try {
            $user = $action->handle($credentials);
        } catch (UnauthorizedException) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->onlyInput('email');
        } catch (ForbiddenException $exception) {
            return back()
                ->withErrors(['email' => $exception->getMessage()])
                ->onlyInput('email');
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
