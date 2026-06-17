<?php

namespace App\Services;

use App\Support\TwilioConfiguration;
use App\Support\WhatsAppPhone;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class TwilioWhatsAppService extends Service
{
    /**
     * @param  array<string, string>  $contentVariables
     */
    public function sendTemplate(string $normalizedPhone, string $contentSid, array $contentVariables): void
    {
        $this->assertMessagingReady();
        $this->assertTemplateSid($contentSid);

        $response = $this->request([
            'From' => $this->whatsappFrom(),
            'MessagingServiceSid' => (string) config('services.twilio.messaging_service_sid'),
            'ContentSid' => $contentSid,
            'To' => WhatsAppPhone::twilioAddress($normalizedPhone),
            'ContentVariables' => json_encode($contentVariables, JSON_THROW_ON_ERROR),
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Twilio WhatsApp request failed with HTTP '.$response->status().'.'
            );
        }
    }

    public function sendOtp(string $normalizedPhone, string $code): void
    {
        $templateSid = TwilioConfiguration::templateSid('otp_template_sid');

        if ($templateSid === null) {
            throw new RuntimeException(
                TwilioConfiguration::missingTemplateMessage('TWILIO_OTP_TEMPLATE_SID')
            );
        }

        $this->sendTemplate($normalizedPhone, $templateSid, [
            '1' => $code,
        ]);
    }

    /**
     * @param  array{1: string, 2: string}  $placeholders
     */
    public function sendCompletion(string $normalizedPhone, string $templateSid, array $placeholders): void
    {
        $this->sendTemplate($normalizedPhone, $templateSid, $placeholders);
    }

    public function isConfigured(): bool
    {
        return TwilioConfiguration::isMessagingConfigured();
    }

    public function assertMessagingReady(): void
    {
        if (! $this->isConfigured()) {
            Log::warning('Twilio WhatsApp messaging attempted without configuration.');

            throw new RuntimeException(TwilioConfiguration::missingMessagingConfigurationMessage());
        }
    }

    private function assertTemplateSid(string $contentSid): void
    {
        if ($contentSid === '') {
            throw new RuntimeException('Twilio WhatsApp template SID is required.');
        }
    }

    /**
     * @param  array<string, string>  $body
     */
    private function request(array $body): Response
    {
        $accountSid = (string) config('services.twilio.account_sid');

        return Http::asForm()
            ->withBasicAuth($accountSid, (string) config('services.twilio.auth_token'))
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", $body);
    }

    private function whatsappFrom(): string
    {
        $from = (string) config('services.twilio.whatsapp_from');

        return str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from;
    }
}
