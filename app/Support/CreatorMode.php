<?php

namespace App\Support;

use App\Models\DuaList;

class CreatorMode
{
    public const MODE_CREATOR = 'creator';

  /**
     * Allowed donation link hostnames (WordPress parity).
     *
     * @var list<string>
     */
    public const ALLOWED_DONATION_HOSTS = [
        'launchgood.com',
        'www.launchgood.com',
        'justgiving.com',
        'www.justgiving.com',
        'gofundme.com',
        'www.gofundme.com',
        'muslimgiving.org',
        'www.muslimgiving.org',
        'givematch.com',
        'www.givematch.com',
        'givebrite.com',
        'www.givebrite.com',
    ];

    public static function enabled(): bool
    {
        return (bool) config('mydualist.creator_mode.enabled');
    }

    public static function isCreatorList(?DuaList $duaList): bool
    {
        return $duaList !== null && $duaList->list_mode === self::MODE_CREATOR;
    }

    public static function showsCreatorFeatures(?DuaList $duaList): bool
    {
        return self::enabled() && self::isCreatorList($duaList);
    }

    public static function hasFundraisingContent(?DuaList $duaList): bool
    {
        return self::showsCreatorFeatures($duaList)
            && filled($duaList->donation_link)
            && filled($duaList->donation_note);
    }

    /**
     * @return array<int, string|\Illuminate\Contracts\Validation\ValidationRule>
     */
    public static function donationLinkRules(bool $required = true): array
    {
        $rules = [
            $required ? 'required' : 'nullable',
            'string',
            'url',
            'max:2048',
            'regex:'.self::donationLinkPattern(),
        ];

        return $rules;
    }

    public static function donationLinkPattern(): string
    {
        return '/^(https?:\/\/)(www\.)?(launchgood\.com|justgiving\.com|gofundme\.com|muslimgiving\.org|givematch\.com|givebrite\.com)(\/[^\s]*)?$/i';
    }

    public static function isAllowedDonationUrl(string $url): bool
    {
        return (bool) preg_match(self::donationLinkPattern(), $url);
    }
}
