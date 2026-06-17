<?php

namespace App\Services;

use App\Support\TwilioConfiguration;
use App\Support\WhatsAppPhone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WhatsAppOtpService extends Service
{
    private const OTP_TTL_SECONDS = 300;

    private const VERIFICATION_TOKEN_TTL_MINUTES = 30;

    public function __construct(
        private readonly TwilioWhatsAppService $twilio,
    ) {}

    public function send(string $countryCode, string $phone): void
    {
        $normalizedPhone = $this->validatedPhone($countryCode, $phone);
        $code = $this->generateCode();

        Cache::put($this->otpCacheKey($normalizedPhone), $code, now()->addSeconds(self::OTP_TTL_SECONDS));

        if (TwilioConfiguration::allowsTestOtpBypass()) {
            return;
        }

        if (! $this->twilio->isConfigured()) {
            throw ValidationException::withMessages([
                'whatsapp_phone' => TwilioConfiguration::missingMessagingConfigurationMessage(),
            ]);
        }

        $this->twilio->sendOtp($normalizedPhone, $code);
    }

    /**
     * @return array{token: string, phone: string}
     */
    public function verify(string $countryCode, string $phone, string $otp): array
    {
        $normalizedPhone = $this->validatedPhone($countryCode, $phone);
        $expected = Cache::get($this->otpCacheKey($normalizedPhone));

        if (! is_string($expected) || $expected === '') {
            throw ValidationException::withMessages([
                'otp' => 'Code Expired. Please resend code',
            ]);
        }

        if (! hash_equals($expected, trim($otp))) {
            throw ValidationException::withMessages([
                'otp' => 'Invalid Authentication Code',
            ]);
        }

        Cache::forget($this->otpCacheKey($normalizedPhone));

        $token = Str::random(64);

        Cache::put($this->verificationTokenCacheKey($token), [
            'country_code' => $countryCode,
            'phone' => $phone,
            'normalized_phone' => $normalizedPhone,
        ], now()->addMinutes(self::VERIFICATION_TOKEN_TTL_MINUTES));

        return [
            'token' => $token,
            'phone' => $normalizedPhone,
        ];
    }

    /**
     * @return array{country_code: string, phone: string, normalized_phone: string}
     */
    public function consumeVerificationToken(string $token): array
    {
        $payload = Cache::pull($this->verificationTokenCacheKey($token));

        if (! is_array($payload) || empty($payload['normalized_phone'])) {
            throw ValidationException::withMessages([
                'whatsapp_verification_token' => 'WhatsApp verification has expired. Please verify your phone number again.',
            ]);
        }

        return [
            'country_code' => (string) $payload['country_code'],
            'phone' => (string) $payload['phone'],
            'normalized_phone' => (string) $payload['normalized_phone'],
        ];
    }

    public function otpTtlSeconds(): int
    {
        return self::OTP_TTL_SECONDS;
    }

    public function otpLength(): int
    {
        return 6;
    }

    private function validatedPhone(string $countryCode, string $phone): string
    {
        if (! WhatsAppPhone::isValid($countryCode, $phone)) {
            throw ValidationException::withMessages([
                'whatsapp_phone' => 'Error: Invalid Phone Number',
            ]);
        }

        return WhatsAppPhone::normalize($countryCode, $phone);
    }

    private function generateCode(): string
    {
        if (TwilioConfiguration::allowsTestOtpBypass()) {
            $testOtp = (string) config('services.twilio.test_otp');

            return str_pad($testOtp, $this->otpLength(), '0', STR_PAD_LEFT);
        }

        return str_pad((string) random_int(0, 999999), $this->otpLength(), '0', STR_PAD_LEFT);
    }

    private function otpCacheKey(string $normalizedPhone): string
    {
        return 'whatsapp-otp:'.$normalizedPhone;
    }

    private function verificationTokenCacheKey(string $token): string
    {
        return 'whatsapp-verification-token:'.$token;
    }
}
