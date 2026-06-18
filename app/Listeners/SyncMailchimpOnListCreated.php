<?php

namespace App\Listeners;

use App\Events\DuaListCreated;
use App\Jobs\SyncMailchimpMemberToListJob;
use App\Support\MailchimpConfiguration;
use App\Support\MailchimpTag;
use Illuminate\Support\Carbon;

class SyncMailchimpOnListCreated
{
    public function handle(DuaListCreated $event): void
    {
        if (! MailchimpConfiguration::isEnabled()) {
            return;
        }

        $duaList = $event->duaList->loadMissing('user');
        $owner = $duaList->user;

        if ($owner === null || blank($owner->email)) {
            return;
        }

        SyncMailchimpMemberToListJob::dispatch([
            'email' => $owner->email,
            'first_name' => $owner->first_name,
            'last_name' => $owner->last_name,
            'category' => $duaList->occasion,
            'list_name' => $duaList->title,
            'start_date' => $this->formatDate($duaList->start_date),
            'end_date' => $this->formatDate($duaList->end_date),
        ], MailchimpTag::ListCreator);
    }

    private function formatDate(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date)->format('d/m/Y');
    }
}
