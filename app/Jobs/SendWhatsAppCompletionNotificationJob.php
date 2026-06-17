<?php

namespace App\Jobs;

use App\Domains\Billing\Services\UserEntitlementService;
use App\Models\DuaSubmission;
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

class SendWhatsAppCompletionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $submissionId,
    ) {}

    public function handle(
        TwilioWhatsAppService $twilio,
        UserEntitlementService $entitlements,
    ): void {
        if (! $twilio->isConfigured()) {
            return;
        }

        $submission = DuaSubmission::query()
            ->with(['duaList.user'])
            ->find($this->submissionId);

        if ($submission === null) {
            return;
        }

        if ($submission->is_personal_dua || $submission->whatsapp_verified_at === null) {
            return;
        }

        if (blank($submission->whatsapp_country_code) || blank($submission->whatsapp_phone)) {
            return;
        }

        $owner = $submission->duaList?->user;

        if ($owner === null || ! $entitlements->canViewSubmission($owner, $submission)) {
            return;
        }

        $cacheKey = 'whatsapp-completion-sent:'.$submission->id;

        if (! Cache::add($cacheKey, true, now()->addYear())) {
            return;
        }

        $normalizedPhone = WhatsAppPhone::normalize(
            (string) $submission->whatsapp_country_code,
            (string) $submission->whatsapp_phone,
        );

        $submitterName = filled($submission->first_name)
            ? (string) $submission->first_name
            : 'Someone';

        $ownerName = filled($owner->first_name)
            ? (string) $owner->first_name
            : 'Someone';

        $templateSid = $submission->duaList?->occasion === 'salawat'
            ? (string) config('services.twilio.salawat_template_sid')
            : (string) config('services.twilio.completion_template_sid');

        if ($templateSid === '') {
            Cache::forget($cacheKey);

            return;
        }

        try {
            $twilio->sendCompletion($normalizedPhone, $templateSid, [
                '1' => $submitterName,
                '2' => $ownerName,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            Log::error('WhatsApp completion notification failed.', [
                'submission_id' => $submission->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
