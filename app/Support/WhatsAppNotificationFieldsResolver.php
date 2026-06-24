<?php

namespace App\Support;

use App\Services\WhatsAppOtpService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WhatsAppNotificationFieldsResolver
{
    public function __construct(
        private readonly WhatsAppOtpService $whatsappOtp,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{whatsapp_country_code: ?string, whatsapp_phone: ?string, whatsapp_verified_at: ?Carbon}
     */
    public function resolve(array $data): array
    {
        $wantsNotifications = (bool) ($data['whatsapp_notifications'] ?? false);

        if (! $wantsNotifications) {
            return [
                'whatsapp_country_code' => null,
                'whatsapp_phone' => null,
                'whatsapp_verified_at' => null,
            ];
        }

        $countryCode = (string) ($data['whatsapp_country_code'] ?? '');
        $phone = (string) ($data['whatsapp_phone'] ?? '');
        $token = (string) ($data['whatsapp_verification_token'] ?? '');

        if ($token === '') {
            throw ValidationException::withMessages([
                'whatsapp_verification_token' => 'Please verify your WhatsApp number before submitting.',
            ]);
        }

        $verified = $this->whatsappOtp->consumeVerificationToken($token);

        $submittedPhone = WhatsAppPhone::normalize($countryCode, $phone);

        if ($verified['normalized_phone'] !== $submittedPhone) {
            throw ValidationException::withMessages([
                'whatsapp_phone' => 'Verified phone number does not match the submitted number.',
            ]);
        }

        return [
            'whatsapp_country_code' => $countryCode,
            'whatsapp_phone' => $phone,
            'whatsapp_verified_at' => now(),
        ];
    }
}
