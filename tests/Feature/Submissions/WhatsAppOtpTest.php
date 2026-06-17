<?php

use App\Domains\Billing\Services\UserEntitlementService;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Jobs\SendWhatsAppCompletionNotificationJob;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\TwilioWhatsAppService;
use App\Services\WhatsAppOtpService;
use App\Support\TwilioConfiguration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

const TWILIO_TEST_ACCOUNT_SID = 'AC00000000000000000000000000000001';
const TWILIO_TEST_AUTH_TOKEN = 'test-auth-token';
const TWILIO_TEST_MESSAGING_SID = 'MG00000000000000000000000000000001';
const TWILIO_TEST_WHATSAPP_FROM = 'whatsapp:+10000000000';
const TWILIO_TEST_OTP_TEMPLATE = 'HX00000000000000000000000000000001';
const TWILIO_TEST_COMPLETION_TEMPLATE = 'HX00000000000000000000000000000002';
const TWILIO_TEST_SALAWAT_TEMPLATE = 'HX00000000000000000000000000000003';

function configureTwilioTestBypass(): void
{
    config([
        'app.env' => 'local',
        'services.twilio.test_otp' => '123456',
        'services.twilio.account_sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.messaging_service_sid' => null,
        'services.twilio.whatsapp_from' => null,
        'services.twilio.otp_template_sid' => TWILIO_TEST_OTP_TEMPLATE,
        'services.twilio.completion_template_sid' => TWILIO_TEST_COMPLETION_TEMPLATE,
        'services.twilio.salawat_template_sid' => TWILIO_TEST_SALAWAT_TEMPLATE,
    ]);
}

function configureTwilioOutbound(): void
{
    config([
        'app.env' => 'local',
        'services.twilio.test_otp' => 'random',
        'services.twilio.account_sid' => TWILIO_TEST_ACCOUNT_SID,
        'services.twilio.auth_token' => TWILIO_TEST_AUTH_TOKEN,
        'services.twilio.messaging_service_sid' => TWILIO_TEST_MESSAGING_SID,
        'services.twilio.whatsapp_from' => TWILIO_TEST_WHATSAPP_FROM,
        'services.twilio.otp_template_sid' => TWILIO_TEST_OTP_TEMPLATE,
        'services.twilio.completion_template_sid' => TWILIO_TEST_COMPLETION_TEMPLATE,
        'services.twilio.salawat_template_sid' => TWILIO_TEST_SALAWAT_TEMPLATE,
    ]);
}

beforeEach(function () {
    configureTwilioTestBypass();
});

function sendAndVerifyWhatsAppOtp(string $countryCode = '+44', string $phone = '7700900123'): string
{
    app(WhatsAppOtpService::class)->send($countryCode, $phone);

    return app(WhatsAppOtpService::class)->verify($countryCode, $phone, '123456')['token'];
}

test('otp send stores a six digit code with five minute expiry', function () {
    $service = app(WhatsAppOtpService::class);

    $service->send('+44', '7700900123');

    expect(Cache::get('whatsapp-otp:+447700900123'))->toBe('123456')
        ->and($service->otpLength())->toBe(6)
        ->and($service->otpTtlSeconds())->toBe(300);
});

test('otp send endpoint returns success payload', function () {
    $this->postJson('/api/v1/public/submissions/otp/send', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
    ])->assertOk()
        ->assertJsonPath('data.expires_in', 300)
        ->assertJsonPath('data.otp_length', 6);
});

test('otp verify endpoint returns verification token', function () {
    app(WhatsAppOtpService::class)->send('+44', '7700900123');

    $response = $this->postJson('/api/v1/public/submissions/otp/verify', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'otp' => '123456',
    ])->assertOk()
        ->assertJsonPath('message', 'Phone number verified successfully!')
        ->assertJsonPath('data.phone', '+447700900123');

    expect(strlen((string) $response->json('data.verification_token')))->toBe(64);
});

test('otp verify rejects invalid code with wordpress parity message', function () {
    app(WhatsAppOtpService::class)->send('+44', '7700900123');

    $this->postJson('/api/v1/public/submissions/otp/verify', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'otp' => '000000',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['otp'])
        ->assertJsonPath('errors.otp.0', 'Invalid Authentication Code');
});

test('otp verify rejects expired code with wordpress parity message', function () {
    Cache::forget('whatsapp-otp:+447700900123');

    $this->postJson('/api/v1/public/submissions/otp/verify', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'otp' => '123456',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['otp'])
        ->assertJsonPath('errors.otp.0', 'Code Expired. Please resend code');
});

test('otp send is rate limited', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/public/submissions/otp/send', [
            'whatsapp_country_code' => '+44',
            'whatsapp_phone' => '7700900123',
        ])->assertOk();
    }

    $this->postJson('/api/v1/public/submissions/otp/send', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
    ])->assertStatus(429);
});

test('otp send rejects missing twilio configuration outside test bypass mode', function () {
    config([
        'app.env' => 'local',
        'services.twilio.test_otp' => null,
        'services.twilio.account_sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.messaging_service_sid' => null,
        'services.twilio.whatsapp_from' => null,
    ]);

    $this->postJson('/api/v1/public/submissions/otp/send', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['whatsapp_phone'])
        ->assertJsonPath('errors.whatsapp_phone.0', TwilioConfiguration::missingMessagingConfigurationMessage());
});

test('twilio test otp bypass skips outbound api calls', function () {
    Http::fake();

    app(WhatsAppOtpService::class)->send('+44', '7700900123');

    Http::assertNothingSent();
    expect(Cache::get('whatsapp-otp:+447700900123'))->toBe('123456');
});

test('twilio test otp bypass is disabled in production', function () {
    config([
        'app.env' => 'production',
        'services.twilio.test_otp' => '123456',
        'services.twilio.account_sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.messaging_service_sid' => null,
        'services.twilio.whatsapp_from' => null,
    ]);

    expect(TwilioConfiguration::allowsTestOtpBypass())->toBeFalse();

    $this->postJson('/api/v1/public/submissions/otp/send', [
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['whatsapp_phone']);
});

test('public submission persists whatsapp fields after verification', function () {
    DuaList::factory()->create(['slug' => 'whatsapp-list']);
    $token = sendAndVerifyWhatsAppOtp();

    $this->postJson('/api/v1/public/lists/whatsapp-list/submissions', [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'female',
        'terms' => '1',
        'whatsapp_notifications' => '1',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verification_token' => $token,
        'content' => 'Please make dua for my parents.',
    ])->assertCreated();

    $submission = DuaSubmission::query()->first();

    expect($submission)->not->toBeNull()
        ->and($submission->whatsapp_country_code)->toBe('+44')
        ->and($submission->whatsapp_phone)->toBe('7700900123')
        ->and($submission->whatsapp_verified_at)->not->toBeNull();
});

test('public submission rejects whatsapp notifications without verification token', function () {
    DuaList::factory()->create(['slug' => 'verify-required-list']);

    $this->postJson('/api/v1/public/lists/verify-required-list/submissions', [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'female',
        'terms' => '1',
        'whatsapp_notifications' => '1',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'content' => 'Please make dua for my parents.',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['whatsapp_verification_token']);
});

test('public submission persists anonymous flag', function () {
    DuaList::factory()->create(['slug' => 'anonymous-list']);

    $this->postJson('/api/v1/public/lists/anonymous-list/submissions', [
        'first_name' => 'Hidden',
        'last_name' => 'User',
        'email' => 'hidden@example.com',
        'gender' => 'male',
        'terms' => '1',
        'is_anonymous' => '1',
        'content' => 'Please make dua for me.',
    ])->assertCreated();

    expect(DuaSubmission::query()->first()->is_anonymous)->toBeTrue();
});

test('public submission without whatsapp leaves whatsapp fields empty', function () {
    DuaList::factory()->create(['slug' => 'no-whatsapp-list']);

    $this->postJson('/api/v1/public/lists/no-whatsapp-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest@example.com',
        'gender' => 'male',
        'terms' => '1',
        'content' => 'Please make dua.',
    ])->assertCreated();

    $submission = DuaSubmission::query()->first();

    expect($submission->whatsapp_country_code)->toBeNull()
        ->and($submission->whatsapp_phone)->toBeNull()
        ->and($submission->whatsapp_verified_at)->toBeNull();
});

test('completion dispatches whatsapp notification job for verified submissions', function () {
    Queue::fake();

    $owner = User::factory()->create(['first_name' => 'Arsalan']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id, 'occasion' => 'hajj']);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'email' => 'submitter@example.com',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verified_at' => now(),
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    Queue::assertPushed(SendWhatsAppCompletionNotificationJob::class, function (SendWhatsAppCompletionNotificationJob $job) use ($submission) {
        return $job->submissionId === $submission->id;
    });
});

test('send whatsapp completion notification job is queued asynchronously', function () {
    expect(new SendWhatsAppCompletionNotificationJob(1))->toBeInstanceOf(ShouldQueue::class);
});

test('whatsapp completion job sends dua template with placeholder variables', function () {
    configureTwilioOutbound();

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $owner = User::factory()->create(['first_name' => 'Arsalan']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id, 'occasion' => 'hajj']);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Hassan',
        'email' => 'submitter@example.com',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verified_at' => now(),
    ]);

    (new SendWhatsAppCompletionNotificationJob($submission->id))->handle(
        app(TwilioWhatsAppService::class),
        app(UserEntitlementService::class),
    );

    Http::assertSent(function ($request) {
        parse_str((string) $request->body(), $body);

        return $body['ContentSid'] === TWILIO_TEST_COMPLETION_TEMPLATE
            && $body['ContentVariables'] === '{"1":"Hassan","2":"Arsalan"}'
            && $body['To'] === 'whatsapp:+447700900123';
    });
});

test('whatsapp completion job uses salawat template for salawat lists', function () {
    configureTwilioOutbound();

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $owner = User::factory()->create(['first_name' => 'Amina']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id, 'occasion' => 'salawat']);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Yusuf',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verified_at' => now(),
    ]);

    (new SendWhatsAppCompletionNotificationJob($submission->id))->handle(
        app(TwilioWhatsAppService::class),
        app(UserEntitlementService::class),
    );

    Http::assertSent(function ($request) {
        parse_str((string) $request->body(), $body);

        return $body['ContentSid'] === TWILIO_TEST_SALAWAT_TEMPLATE;
    });
});

test('whatsapp completion job logs failures without phone numbers', function () {
    configureTwilioOutbound();

    Http::fake([
        'api.twilio.com/*' => Http::response(['message' => 'error'], 400),
    ]);

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context = []): bool {
            return $message === 'WhatsApp completion notification failed.'
                && array_key_exists('submission_id', $context)
                && array_key_exists('message', $context)
                && ! array_key_exists('phone', $context);
        });

    $owner = User::factory()->create(['first_name' => 'Arsalan']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Hassan',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verified_at' => now(),
    ]);

    (new SendWhatsAppCompletionNotificationJob($submission->id))->handle(
        app(TwilioWhatsAppService::class),
        app(UserEntitlementService::class),
    );
});

test('whatsapp otp send uses twilio content template when configured', function () {
    configureTwilioOutbound();

    Http::fake([
        'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    app(WhatsAppOtpService::class)->send('+44', '7700900123');

    Http::assertSent(function ($request) {
        parse_str((string) $request->body(), $body);

        return $body['ContentSid'] === TWILIO_TEST_OTP_TEMPLATE
            && str_contains((string) $body['ContentVariables'], '"1":"')
            && $body['To'] === 'whatsapp:+447700900123';
    });
});

test('create submission action rejects mismatched verified phone', function () {
    $list = DuaList::factory()->create();
    $token = sendAndVerifyWhatsAppOtp('+44', '7700900123');

    expect(fn () => app(CreateDuaSubmissionAction::class)($list, [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'email' => 'sara@example.com',
        'gender' => 'female',
        'whatsapp_notifications' => true,
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900999',
        'whatsapp_verification_token' => $token,
        'content' => 'Please make dua.',
    ]))->toThrow(ValidationException::class);
});

test('twilio messaging service throws a clear configuration exception', function () {
    config([
        'services.twilio.account_sid' => null,
        'services.twilio.auth_token' => null,
        'services.twilio.messaging_service_sid' => null,
        'services.twilio.whatsapp_from' => null,
    ]);

    expect(fn () => app(TwilioWhatsAppService::class)->assertMessagingReady())
        ->toThrow(\RuntimeException::class, TwilioConfiguration::missingMessagingConfigurationMessage());
});
