<?php

namespace App\Listeners;

use App\Events\CommunityDuaCompletedByPilgrim;
use App\Jobs\SendCommunityDuaWhatsAppCompletionNotificationJob;

class SendCommunityDuaCompletedWhatsApp
{
    public function handle(CommunityDuaCompletedByPilgrim $event): void
    {
        $communityDua = $event->communityDua;

        if ($communityDua->whatsapp_verified_at === null) {
            return;
        }

        if (blank($communityDua->whatsapp_country_code) || blank($communityDua->whatsapp_phone)) {
            return;
        }

        SendCommunityDuaWhatsAppCompletionNotificationJob::dispatch(
            $communityDua->id,
            $event->completedBy->id,
        );
    }
}
