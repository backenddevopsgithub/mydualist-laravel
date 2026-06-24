<?php

use App\Domains\Community\Actions\CompleteCommunityDuaAction;
use App\Domains\Notifications\Notifications\CommunityDuaCompletedNotification;
use App\Enums\CommunityDuaStatus;
use App\Jobs\SendCommunityDuaWhatsAppCompletionNotificationJob;
use App\Models\CommunityDua;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\WhatsAppOtpService;
use App\Enums\DuaSubmissionStatus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

/**
 * @return array<string, mixed>
 */
function validCommunityDuaBatchSevenPayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'gender' => 'female',
        'content' => 'Please make dua for her family.',
        'terms' => '1',
    ], $overrides);
}

function verifyCommunityDuaWhatsAppOtp(string $countryCode = '+44', string $phone = '7700900123'): string
{
    app(WhatsAppOtpService::class)->send($countryCode, $phone);

    return app(WhatsAppOtpService::class)->verify($countryCode, $phone, '123456')['token'];
}

beforeEach(function (): void {
    config(['services.twilio.test_otp' => '123456']);
});

test('community dua page shows production explanation content above the form', function () {
    $this->get(route('community-dua.create'))
        ->assertOk()
        ->assertSee('submitting a dua to the general Muslim community', false)
        ->assertSee('How it works:', false)
        ->assertSee('Community Duas allow for anyone in the world to submit a Du', false)
        ->assertSee('Submit a dua request', false)
        ->assertSee('Would you like a Whatsapp notification when a pilgrim completes your dua?', false);
});

test('community dua page includes the marketing footer', function () {
    $this->get(route('community-dua.create'))
        ->assertOk()
        ->assertSee('Your companion for collecting, organizing and sharing duas with ease', false)
        ->assertSee('Dua Resources', false);
});

test('community dua success page includes the marketing footer', function () {
    $this->get(route('community-dua.success'))
        ->assertOk()
        ->assertSee('Your companion for collecting, organizing and sharing duas with ease', false);
});

test('community dua submission stores verified whatsapp preference', function () {
    $token = verifyCommunityDuaWhatsAppOtp();

    $this->post(route('community-dua.store'), validCommunityDuaBatchSevenPayload([
        'whatsapp_notifications' => '1',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verification_token' => $token,
    ]))->assertRedirect(route('community-dua.create'))
        ->assertSessionHas('status');

    $dua = CommunityDua::query()->first();

    expect($dua)->not->toBeNull()
        ->and($dua->whatsapp_country_code)->toBe('+44')
        ->and($dua->whatsapp_phone)->toBe('7700900123')
        ->and($dua->whatsapp_verified_at)->not->toBeNull();
});

test('community dua submission requires whatsapp verification when opted in', function () {
    $this->from(route('community-dua.create'))
        ->post(route('community-dua.store'), validCommunityDuaBatchSevenPayload([
            'whatsapp_notifications' => '1',
            'whatsapp_country_code' => '+44',
            'whatsapp_phone' => '7700900123',
        ]))
        ->assertRedirect(route('community-dua.create'))
        ->assertSessionHasErrors(['whatsapp_verification_token']);

    expect(CommunityDua::query()->count())->toBe(0);
});

test('community dua completion dispatches whatsapp notification job when verified', function () {
    Queue::fake();
    Notification::fake();

    $owner = User::factory()->create(['first_name' => 'Yusuf']);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed->value,
        'completed_at' => now(),
    ]);

    $communityDua = CommunityDua::factory()->free()->create([
        'email' => 'submitter@example.com',
        'whatsapp_country_code' => '+44',
        'whatsapp_phone' => '7700900123',
        'whatsapp_verified_at' => now(),
    ]);

    app(CompleteCommunityDuaAction::class)($communityDua, $owner);

    Queue::assertPushed(SendCommunityDuaWhatsAppCompletionNotificationJob::class, function ($job) use ($communityDua, $owner) {
        return $job->communityDuaId === $communityDua->id
            && $job->completedByUserId === $owner->id;
    });

    Notification::assertSentOnDemand(CommunityDuaCompletedNotification::class);
});

test('community dua completion does not dispatch whatsapp job without verified phone', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed->value,
        'completed_at' => now(),
    ]);

    $communityDua = CommunityDua::factory()->free()->create([
        'email' => 'submitter@example.com',
    ]);

    app(CompleteCommunityDuaAction::class)($communityDua, $owner);

    Queue::assertNotPushed(SendCommunityDuaWhatsAppCompletionNotificationJob::class);
});
