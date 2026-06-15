<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Notifications\Notifications\CommunityDuaCompletedNotification;
use App\Domains\Notifications\Notifications\DuaCompletedNotification;
use App\Domains\Notifications\Notifications\ListCreatedNotification;
use App\Models\CommunityDua;
use App\Domains\Notifications\Notifications\NewSubmissionNotification;
use App\Domains\Notifications\Notifications\SubmissionQuotaWarningNotification;
use App\Domains\Notifications\Notifications\WelcomeUserNotification;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class TransactionalEmailService extends Service
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    public function sendWelcomeIfNeeded(User $user): void
    {
        DB::transaction(function () use ($user): void {
            /** @var User $lockedUser */
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($lockedUser->welcome_email_sent_at !== null) {
                return;
            }

            $lockedUser->notify(new WelcomeUserNotification);
            $lockedUser->forceFill(['welcome_email_sent_at' => now()])->save();
        });
    }

    public function sendListCreatedIfNeeded(DuaList $duaList): void
    {
        DB::transaction(function () use ($duaList): void {
            /** @var DuaList $lockedList */
            $lockedList = DuaList::query()
                ->with('user')
                ->whereKey($duaList->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedList->list_created_email_sent_at !== null) {
                return;
            }

            $owner = $lockedList->user;

            if ($owner === null || ! $owner->hasVerifiedEmail()) {
                return;
            }

            $owner->notify(new ListCreatedNotification($lockedList));
            $lockedList->forceFill(['list_created_email_sent_at' => now()])->save();
        });
    }

    public function sendPendingListCreatedEmails(User $user): void
    {
        $user->duaLists()
            ->whereNull('list_created_email_sent_at')
            ->orderBy('id')
            ->each(fn (DuaList $duaList): mixed => $this->sendListCreatedIfNeeded($duaList));
    }

    /**
     * @param  Collection<int, DuaSubmission>  $submissions
     */
    public function handleSubmissionsCreated(DuaList $duaList, Collection $submissions, int $nonPersonalCountBefore): void
    {
        $duaList->loadMissing('user');
        $owner = $duaList->user;

        if ($owner === null) {
            return;
        }

        $publicSubmissions = $submissions->filter(fn (DuaSubmission $submission): bool => ! $submission->is_personal_dua);

        if ($publicSubmissions->isEmpty()) {
            return;
        }

        if ($this->shouldSendImmediateSubmissionEmails($duaList)) {
            $publicSubmissions->each(
                fn (DuaSubmission $submission): mixed => $owner->notify(new NewSubmissionNotification($submission))
            );
        }

        $nonPersonalCountAfter = $nonPersonalCountBefore + $publicSubmissions->count();
        $this->sendQuotaWarningIfNeeded($duaList, $owner, $nonPersonalCountBefore, $nonPersonalCountAfter);
    }

    public function sendCommunityDuaCompletion(CommunityDua $communityDua, User $completedBy): void
    {
        if (blank($communityDua->email)) {
            return;
        }

        Notification::route('mail', $communityDua->email)
            ->notify(new CommunityDuaCompletedNotification($communityDua, $completedBy));
    }

    public function sendCompletionIfNeeded(DuaSubmission $submission): void
    {
        DB::transaction(function () use ($submission): void {
            /** @var DuaSubmission $lockedSubmission */
            $lockedSubmission = DuaSubmission::query()
                ->with(['duaList.user'])
                ->whereKey($submission->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedSubmission->completion_notified_at !== null) {
                return;
            }

            if ($lockedSubmission->is_personal_dua || blank($lockedSubmission->email)) {
                return;
            }

            $owner = $lockedSubmission->duaList?->user;

            if ($owner === null || ! $this->entitlements->canViewSubmission($owner, $lockedSubmission)) {
                return;
            }

            Notification::route('mail', $lockedSubmission->email)
                ->notify(new DuaCompletedNotification($lockedSubmission));

            $lockedSubmission->forceFill(['completion_notified_at' => now()])->save();
        });
    }

    private function shouldSendImmediateSubmissionEmails(DuaList $duaList): bool
    {
        return ($duaList->email_frequency ?? 'every_submission') === 'every_submission';
    }

    private function sendQuotaWarningIfNeeded(
        DuaList $duaList,
        User $owner,
        int $countBefore,
        int $countAfter,
    ): void {
        if ($this->entitlements->hasPremium($owner)) {
            return;
        }

        $limit = (int) config('mydualist.billing.free_visible_submissions_per_list', 25);
        $remainingBefore = max(0, $limit - $countBefore);
        $remainingAfter = max(0, $limit - $countAfter);

        if ($remainingBefore <= 5 || $remainingAfter > 5) {
            return;
        }

        DB::transaction(function () use ($duaList, $owner): void {
            /** @var DuaList $lockedList */
            $lockedList = DuaList::query()->whereKey($duaList->id)->lockForUpdate()->firstOrFail();

            if ($lockedList->submission_quota_warning_sent_at !== null) {
                return;
            }

            $owner->notify(new SubmissionQuotaWarningNotification($lockedList));
            $lockedList->forceFill(['submission_quota_warning_sent_at' => now()])->save();
        });
    }
}
