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
    case UserListSubmissions = 'user_list_submissions';

    public function label(): string
    {
        return match ($this) {
            self::DuaListAnalytics => 'Dua List Analytics',
            self::UserAnalytics => 'User Analytics',
            self::UniqueUsers => 'Unique Users',
            self::CategoryAnalytics => 'Category Analytics',
            self::SubmissionAnalytics => 'Submission Analytics',
            self::KeywordAnalytics => 'Keyword Analytics',
            self::UserListSubmissions => 'List Submissions',
        };
    }

    public function isUserFacing(): bool
    {
        return $this === self::UserListSubmissions;
    }
}
