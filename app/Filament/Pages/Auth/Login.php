<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getRateLimitKey($method, $component = null): string
    {
        return 'admin-login:'.request()->ip();
    }
}
