<?php

namespace App\Jobs;

use App\Models\CommunityDua;
use App\Models\User;
use App\Services\TwilioWhatsAppService;
use App\Support\WhatsAppPhone;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCommunityDuaWhatsAppCompletionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $communityDuaId,
        public int $completedByUserId,
    ) {}

    public function handle(TwilioWhatsAppService $twilio): void
    {
        if (! $twilio->isConfigured()) {
            return;
        }

        $communityDua = CommunityDua::query()->find($this->communityDuaId);
        $pilgrim = User::query()->find($this->completedByUserId);

        if ($communityDua === null || $pilgrim === null) {
            return;
        }

        if ($communityDua->whatsapp_verified_at === null) {
            return;
        }

        if (blank($communityDua->whatsapp_country_code) || blank($communityDua->whatsapp_phone)) {
            return;
        }

        $cacheKey = 'whatsapp-community-completion-sent:'.$communityDua->id.':'.$pilgrim->id;

        if (! Cache::add($cacheKey, true, now()->addYear())) {
            return;
        }

        $normalizedPhone = WhatsAppPhone::normalize(
            (string) $communityDua->whatsapp_country_code,
            (string) $communityDua->whatsapp_phone,
        );

        $submitterName = filled($communityDua->first_name)
            ? (string) $communityDua->first_name
            : 'Someone';

        $pilgrimName = filled($pilgrim->first_name)
            ? (string) $pilgrim->first_name
            : 'Someone';

        $templateSid = (string) config('services.twilio.completion_template_sid');

        if ($templateSid === '') {
            Cache::forget($cacheKey);

            return;
        }

        try {
            $twilio->sendCompletion($normalizedPhone, $templateSid, [
                '1' => $submitterName,
                '2' => $pilgrimName,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            Log::error('Community dua WhatsApp completion notification failed.', [
                'community_dua_id' => $communityDua->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
