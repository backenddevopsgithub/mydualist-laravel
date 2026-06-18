<?php

namespace App\Enums;

enum DuaSubmissionModerationAction: string
{
    case Hide = 'hide';
    case Restore = 'restore';
    case Dismiss = 'dismiss';
    case AutoHide = 'auto_hide';

    public function label(): string
    {
        return match ($this) {
            self::Hide => 'Hide',
            self::Restore => 'Restore',
            self::Dismiss => 'Dismiss report',
            self::AutoHide => 'Auto-hide',
        };
    }
}
