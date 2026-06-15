<?php

namespace App\Domains\Notifications\Support;

use App\Models\DuaList;
use App\Models\User;
use Illuminate\Support\Str;

class EmailPresentation
{
    public static function userFirstName(User $user): string
    {
        $firstName = trim((string) ($user->first_name ?: Str::before((string) $user->name, ' ')));

        return $firstName !== '' ? $firstName : 'there';
    }

    public static function possessivePronoun(User $user): string
    {
        return $user->gender === 'female' ? 'her' : 'his';
    }

    public static function dashboardUrl(): string
    {
        return route('dashboard');
    }

    public static function upgradeUrl(): string
    {
        return route('dashboard.upgrade');
    }

    public static function createListUrl(): string
    {
        return route('onboarding.start');
    }

    public static function listSubmissionsUrl(DuaList $duaList): string
    {
        return route('dashboard.lists.show', $duaList);
    }

    public static function brandLogoUrl(): string
    {
        return asset(config('mydualist.brand.logo_url', 'images/logo-mdl.svg'));
    }
}
