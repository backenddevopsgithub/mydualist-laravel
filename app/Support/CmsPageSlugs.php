<?php

namespace App\Support;

class CmsPageSlugs
{
    public const PRIVACY_POLICY = 'privacy-policy';

    public const TERMS_AND_CONDITIONS = 'terms-and-conditions';

    public const HELP_AND_SUPPORT = 'help-and-support';

    /**
     * @return list<string>
     */
    public static function footerSlugs(): array
    {
        return [
            self::PRIVACY_POLICY,
            self::TERMS_AND_CONDITIONS,
            self::HELP_AND_SUPPORT,
        ];
    }
}
