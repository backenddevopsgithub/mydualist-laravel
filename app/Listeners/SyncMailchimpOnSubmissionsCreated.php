<?php

namespace App\Listeners;

use App\Events\DuaSubmissionsCreated;
use App\Jobs\SyncMailchimpMemberToListJob;
use App\Models\DuaSubmission;
use App\Support\MailchimpConfiguration;
use App\Support\MailchimpTag;
use Illuminate\Support\Collection;

class SyncMailchimpOnSubmissionsCreated
{
    public function handle(DuaSubmissionsCreated $event): void
    {
        if (! MailchimpConfiguration::isEnabled()) {
            return;
        }

        $publicSubmissions = $event->submissions
            ->filter(fn (DuaSubmission $submission): bool => ! $submission->is_personal_dua && filled($submission->email));

        if ($publicSubmissions->isEmpty()) {
            return;
        }

        $this->syncUniqueEmails($publicSubmissions);
    }

    /**
     * @param  Collection<int, DuaSubmission>  $submissions
     */
    private function syncUniqueEmails(Collection $submissions): void
    {
        $submissions
            ->groupBy(fn (DuaSubmission $submission): string => mb_strtolower((string) $submission->email))
            ->each(function (Collection $group, string $email): void {
                /** @var DuaSubmission $sample */
                $sample = $group->first();
                $batchCount = $group->count();
                $totalCount = DuaSubmission::query()->where('email', $email)->count();
                $submissionCount = max(1, $totalCount - $batchCount + 1);

                SyncMailchimpMemberToListJob::dispatch([
                    'email' => $email,
                    'first_name' => $sample->first_name,
                    'last_name' => $sample->last_name,
                    'submission_count' => $submissionCount,
                ], MailchimpTag::Submitter);
            });
    }
}
