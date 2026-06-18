<?php

namespace App\Listeners;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Events\DuaSubmissionCompleted;
use App\Jobs\SyncMailchimpMemberToTagJob;
use App\Support\MailchimpConfiguration;
use App\Support\MailchimpTag;

class SyncMailchimpOnSubmissionCompleted
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    public function handle(DuaSubmissionCompleted $event): void
    {
        if (! MailchimpConfiguration::isEnabled()) {
            return;
        }

        $submission = $event->submission->loadMissing('duaList.user');

        if ($submission->is_personal_dua || blank($submission->email)) {
            return;
        }

        $owner = $submission->duaList?->user;

        if ($owner === null || ! $this->entitlements->canViewSubmission($owner, $submission)) {
            return;
        }

        SyncMailchimpMemberToTagJob::dispatch([
            'email' => $submission->email,
            'first_name' => $submission->first_name,
            'last_name' => $submission->last_name,
        ], MailchimpTag::DuaSubmitterReview, needsValidationOnEmail: true);
    }
}
