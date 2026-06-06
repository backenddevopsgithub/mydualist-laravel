<?php

namespace App\Http\Controllers\Auth;

use App\Domains\Auth\Services\WordPressPasswordService;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        return view('auth.login');
    }

    public function store(LoginRequest $request, WordPressPasswordService $passwordService): RedirectResponse
    {
        $credentials = $request->validated();
        $user = User::query()->where('email', $credentials['email'])->first();

        if ($user === null || ! $passwordService->verify($credentials['password'], $user)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->onlyInput('email');
        }

        if ($user->status !== UserStatus::Active) {
            return back()
                ->withErrors(['email' => 'Your account is not active.'])
                ->onlyInput('email');
        }

        if ($user->wp_password_hash !== null) {
            $user = $passwordService->upgradeFromLegacyHash($user, $credentials['password']);
        }

        Auth::login($user, (bool) ($credentials['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
