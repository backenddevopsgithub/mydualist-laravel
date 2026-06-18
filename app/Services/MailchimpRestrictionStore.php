<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Mirrors WordPress option _mc_restriction_emails used to dedupe tag syncs.
 */
class MailchimpRestrictionStore extends Service
{
    public function contains(string $email): bool
    {
        return DB::table('mailchimp_restriction_emails')
            ->where('email', mb_strtolower($email))
            ->exists();
    }

    public function remember(string $email): void
    {
        DB::table('mailchimp_restriction_emails')->insertOrIgnore([
            'email' => mb_strtolower($email),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
