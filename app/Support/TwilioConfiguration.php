<?php

namespace App\Support;

class TwilioConfiguration
{
    /**
     * @return array<string, string>
     */
    public static function messagingCredentialKeys(): array
    {
        return [
            'account_sid' => 'TWILIO_ACCOUNT_SID',
            'auth_token' => 'TWILIO_AUTH_TOKEN',
            'messaging_service_sid' => 'TWILIO_MESSAGING_SERVICE_SID',
            'whatsapp_from' => 'TWILIO_WHATSAPP_FROM',
        ];
    }

    /**
     * @return list<string>
     */
    public static function missingMessagingCredentials(): array
    {
        $missing = [];

        foreach (self::messagingCredentialKeys() as $configKey => $envKey) {
            if (! filled(config("services.twilio.{$configKey}"))) {
                $missing[] = $envKey;
            }
        }

        return $missing;
    }

    public static function isMessagingConfigured(): bool
    {
        return self::missingMessagingCredentials() === [];
    }

    public static function templateSid(string $configKey): ?string
    {
        $value = config("services.twilio.{$configKey}");

        return filled($value) ? (string) $value : null;
    }

    public static function allowsTestOtpBypass(): bool
    {
        if (config('app.env') === 'production') {
            return false;
        }

        $testOtp = config('services.twilio.test_otp');

        return is_string($testOtp) && $testOtp !== '' && $testOtp !== 'random';
    }

    public static function requiresOutboundMessaging(): bool
    {
        return ! self::allowsTestOtpBypass();
    }

    public static function missingMessagingConfigurationMessage(): string
    {
        $missing = self::missingMessagingCredentials();

        if ($missing === []) {
            return 'Twilio WhatsApp messaging is not configured.';
        }

        return 'Twilio WhatsApp messaging is not configured. Set: '.implode(', ', $missing).'.';
    }

    public static function missingTemplateMessage(string $envKey): string
    {
        return "Twilio WhatsApp template is not configured. Set {$envKey}.";
    }
}
