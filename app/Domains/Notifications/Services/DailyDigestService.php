<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Notifications\Notifications\DailyDigestNotification;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DailyDigestService extends Service
{
    public function __construct(
        private readonly UserEntitlementService $entitlements,
    ) {}

    public function sendPendingDigests(): int
    {
        $sent = 0;
        $chunkSize = (int) config('mydualist.notifications.daily_digest_list_chunk', 50);

        DuaList::query()
            ->where('email_frequency', 'daily_summary')
            ->whereHas('submissions', function ($query): void {
                $query->pendingDigest()->visible();
            })
            ->with('user')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $lists) use (&$sent): void {
                foreach ($lists as $list) {
                    if ($this->sendDigestForList($list)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function pendingDigestSubmissionCount(): int
    {
        return DuaSubmission::query()
            ->pendingDigest()
            ->visible()
            ->whereHas('duaList', fn ($query) => $query->where('email_frequency', 'daily_summary'))
            ->count();
    }

    private function sendDigestForList(DuaList $list): bool
    {
        $owner = $list->user;

        if ($owner === null || ! $owner->hasVerifiedEmail()) {
            return false;
        }

        return DB::transaction(function () use ($list, $owner): bool {
            $submissions = DuaSubmission::query()
                ->where('dua_list_id', $list->id)
                ->pendingDigest()
                ->visible()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->filter(fn (DuaSubmission $submission): bool => $this->entitlements->canViewSubmission($owner, $submission))
                ->values();

            if ($submissions->isEmpty()) {
                return false;
            }

            $owner->notify(new DailyDigestNotification($list, $submissions));

            DuaSubmission::query()
                ->whereIn('id', $submissions->pluck('id'))
                ->update(['digest_sent_at' => now()]);

            return true;
        });
    }
}
