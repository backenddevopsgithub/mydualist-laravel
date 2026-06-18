<?php

namespace App\Enums;

enum AdminExportType: string
{
    case DuaListAnalytics = 'dua_list_analytics';
    case UserAnalytics = 'user_analytics';
    case UniqueUsers = 'unique_users';
    case CategoryAnalytics = 'category_analytics';
    case SubmissionAnalytics = 'submission_analytics';
    case KeywordAnalytics = 'keyword_analytics';

    public function label(): string
    {
        return match ($this) {
            self::DuaListAnalytics => 'Dua List Analytics',
            self::UserAnalytics => 'User Analytics',
            self::UniqueUsers => 'Unique Users',
            self::CategoryAnalytics => 'Category Analytics',
            self::SubmissionAnalytics => 'Submission Analytics',
            self::KeywordAnalytics => 'Keyword Analytics',
        };
    }
}
