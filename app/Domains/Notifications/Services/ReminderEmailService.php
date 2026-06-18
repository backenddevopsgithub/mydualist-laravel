<?php

namespace App\Domains\Notifications\Services;

use App\Domains\Notifications\Notifications\ClosingSoonReminderNotification;
use App\Domains\Notifications\Notifications\ListImageReminderNotification;
use App\Domains\Notifications\Notifications\NoActivityReminderNotification;
use App\Models\DuaList;
use App\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReminderEmailService extends Service
{
    public function sendNoActivityReminders(): int
    {
        $sent = 0;
        $chunkSize = (int) config('mydualist.notifications.reminder_list_chunk', 50);
        $hours = (int) config('mydualist.notifications.no_activity_hours', 24);
        $eligibleBefore = now()->subHours($hours);

        $this->eligibleListQuery()
            ->whereNull('no_activity_reminder_sent_at')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', $eligibleBefore)
            ->whereDoesntHave('submissions')
            ->with('user')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $lists) use (&$sent): void {
                foreach ($lists as $list) {
                    if ($this->sendNoActivityReminderForList($list)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendClosingSoonReminders(): int
    {
        $sent = 0;
        $chunkSize = (int) config('mydualist.notifications.reminder_list_chunk', 50);
        $hoursBeforeEnd = (int) config('mydualist.notifications.closing_soon_hours_before_end', 3);
        $latestEndDate = now()->addHours($hoursBeforeEnd)->toDateString();

        $this->eligibleListQuery()
            ->whereNull('closing_soon_reminder_sent_at')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', $latestEndDate)
            ->whereDoesntHave('submissions')
            ->with('user')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $lists) use (&$sent): void {
                foreach ($lists as $list) {
                    if ($this->sendClosingSoonReminderForList($list)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function sendListImageReminders(): int
    {
        $sent = 0;
        $chunkSize = (int) config('mydualist.notifications.reminder_list_chunk', 50);
        $hoursAfterStart = (int) config('mydualist.notifications.list_image_hours_after_start', 1);
        $latestStartDate = now()->subHours($hoursAfterStart)->toDateString();

        $this->eligibleListQuery()
            ->whereNull('list_image_reminder_sent_at')
            ->whereNull('cover_image_path')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $latestStartDate)
            ->where(function (Builder $query): void {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', now()->toDateString());
            })
            ->with('user')
            ->orderBy('id')
            ->chunkById($chunkSize, function (Collection $lists) use (&$sent): void {
                foreach ($lists as $list) {
                    if ($this->sendListImageReminderForList($list)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    /**
     * @return Builder<DuaList>
     */
    private function eligibleListQuery(): Builder
    {
        return DuaList::query()->active();
    }

    private function sendNoActivityReminderForList(DuaList $list): bool
    {
        return $this->sendReminderForList(
            $list,
            'no_activity_reminder_sent_at',
            fn ($owner, $lockedList) => $owner->notify(new NoActivityReminderNotification($lockedList)),
            fn (DuaList $lockedList): bool => $lockedList->submissions()->doesntExist(),
        );
    }

    private function sendClosingSoonReminderForList(DuaList $list): bool
    {
        return $this->sendReminderForList(
            $list,
            'closing_soon_reminder_sent_at',
            fn ($owner, $lockedList) => $owner->notify(new ClosingSoonReminderNotification($lockedList)),
            fn (DuaList $lockedList): bool => $lockedList->submissions()->doesntExist()
                && $lockedList->end_date !== null
                && ! $lockedList->isExpired(),
        );
    }

    private function sendListImageReminderForList(DuaList $list): bool
    {
        return $this->sendReminderForList(
            $list,
            'list_image_reminder_sent_at',
            fn ($owner, $lockedList) => $owner->notify(new ListImageReminderNotification($lockedList)),
            fn (DuaList $lockedList): bool => blank($lockedList->cover_image_path)
                && $lockedList->start_date !== null
                && ($lockedList->end_date === null || ! $lockedList->isExpired()),
        );
    }

    /**
     * @param  callable(\App\Models\User, DuaList): void  $notify
     * @param  callable(DuaList): bool  $shouldSend
     */
    private function sendReminderForList(DuaList $list, string $sentAtColumn, callable $notify, callable $shouldSend): bool
    {
        $owner = $list->user;

        if ($owner === null || ! $owner->hasVerifiedEmail()) {
            return false;
        }

        return DB::transaction(function () use ($list, $sentAtColumn, $notify, $shouldSend, $owner): bool {
            /** @var DuaList|null $lockedList */
            $lockedList = DuaList::query()
                ->whereKey($list->id)
                ->lockForUpdate()
                ->first();

            if ($lockedList === null || $lockedList->{$sentAtColumn} !== null || ! $shouldSend($lockedList)) {
                return false;
            }

            $notify($owner, $lockedList);
            $lockedList->forceFill([$sentAtColumn => now()])->save();

            return true;
        });
    }
}
