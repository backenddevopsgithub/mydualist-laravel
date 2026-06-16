<?php

use App\Domains\Community\Services\CommunityDuaQueueService;
use App\Domains\Notifications\Notifications\CommunityDuaCompletedNotification;
use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use App\Enums\DuaSubmissionStatus;
use App\Models\CommunityDua;
use App\Models\CommunityDuaCompletion;
use App\Models\CommunityDuaQueueState;
use App\Models\CommunityDuaSkip;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\StripePayment;
use App\Models\User;
use App\Domains\Billing\Services\StripeCheckoutService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Stripe\Checkout\Session;
use Stripe\Event as StripeEvent;

beforeEach(function (): void {
    static $ipSuffix = 200;

    $this->withServerVariables(['REMOTE_ADDR' => '10.200.0.'.(++$ipSuffix)]);
});

/**
 * @return array<string, mixed>
 */
function validCommunityDuaPayload(array $overrides = []): array
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

/**
 * @return array{0: User, 1: DuaList}
 */
function createCaughtUpPilgrim(bool $withPending = false): array
{
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed->value,
        'completed_at' => now(),
    ]);

    if ($withPending) {
        DuaSubmission::factory()->create([
            'dua_list_id' => $duaList->id,
            'status' => DuaSubmissionStatus::Pending->value,
        ]);
    }

    return [$owner, $duaList];
}

test('free community dua submission creates an active visible dua', function () {
    $this->post(route('community-dua.store'), validCommunityDuaPayload())
        ->assertRedirect(route('community-dua.create'))
        ->assertSessionHas('status');

    $dua = CommunityDua::query()->first();

    expect($dua)->not->toBeNull()
        ->and($dua->type)->toBe(CommunityDuaType::Free)
        ->and($dua->status)->toBe(CommunityDuaStatus::Active)
        ->and($dua->required_completions)->toBe(1)
        ->and($dua->is_visible)->toBeTrue();
});

test('community dua submission validates name length gender and word limit', function () {
    $this->from(route('community-dua.create'))
        ->post(route('community-dua.store'), validCommunityDuaPayload([
            'first_name' => str_repeat('a', 16),
            'gender' => '',
            'content' => implode(' ', array_fill(0, 101, 'word')),
            'terms' => '',
        ]))
        ->assertRedirect(route('community-dua.create'))
        ->assertSessionHasErrors(['first_name', 'gender', 'content', 'terms']);

    expect(CommunityDua::query()->count())->toBe(0);
});

test('paid checkout creates pending community dua and redirects to embedded checkout', function () {
    $this->seed(\Database\Seeders\BillingProductSeeder::class);

    app()->instance(\App\Domains\Billing\Services\StripePaymentIntentService::class, new class extends \App\Domains\Billing\Services\StripePaymentIntentService
    {
        public function createForPurchase(\App\Models\BillingPurchase $purchase): array
        {
            return [
                'id' => 'pi_community_paid',
                'client_secret' => 'pi_community_paid_secret',
            ];
        }
    });

    $this->post(route('community-dua.checkout'), validCommunityDuaPayload([
        'email' => 'paid@example.com',
    ]))->assertRedirect();

    $dua = CommunityDua::query()->where('email', 'paid@example.com')->first();
    $purchase = \App\Models\BillingPurchase::query()->where('community_dua_id', $dua?->id)->first();

    expect($dua)->not->toBeNull()
        ->and($dua->type)->toBe(CommunityDuaType::Paid)
        ->and($dua->status)->toBe(CommunityDuaStatus::PendingPayment)
        ->and($dua->required_completions)->toBe(20)
        ->and($dua->is_visible)->toBeFalse()
        ->and($purchase)->not->toBeNull();
});

test('paid community dua activates only after successful payment fulfillment', function () {
    $dua = CommunityDua::factory()->paid()->create([
        'status' => CommunityDuaStatus::PendingPayment,
        'is_visible' => false,
    ]);

    app()->instance(StripeCheckoutService::class, new class($dua) extends StripeCheckoutService
    {
        public function __construct(private readonly CommunityDua $dua) {}

        public function retrieveCheckoutSession(string $sessionId): mixed
        {
            return Session::constructFrom([
                'id' => $sessionId,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_community',
                'amount_total' => 1000,
                'currency' => 'gbp',
                'metadata' => [
                    'entitlement' => 'community_dua_paid',
                    'community_dua_id' => (string) $this->dua->id,
                ],
            ]);
        }
    });

    $this->get(route('community-dua.success', ['session_id' => 'cs_paid_success']))
        ->assertOk();

    $dua->refresh();

    expect($dua->status)->toBe(CommunityDuaStatus::Active)
        ->and($dua->is_visible)->toBeTrue();

    $this->assertDatabaseHas('stripe_payments', [
        'stripe_checkout_session_id' => 'cs_paid_success',
        'status' => StripePayment::STATUS_PAID,
    ]);
});

test('stripe webhook fulfills paid community dua idempotently', function () {
    $dua = CommunityDua::factory()->paid()->create([
        'status' => CommunityDuaStatus::PendingPayment,
        'is_visible' => false,
    ]);

    app()->instance(StripeCheckoutService::class, new class($dua) extends StripeCheckoutService
    {
        public function __construct(private readonly CommunityDua $dua) {}

        public function constructWebhookEvent(string $payload, string $signature): StripeEvent
        {
            return StripeEvent::constructFrom([
                'id' => 'evt_community',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'id' => 'cs_webhook_community',
                        'payment_status' => 'paid',
                        'payment_intent' => 'pi_webhook',
                        'amount_total' => 1000,
                        'currency' => 'gbp',
                        'metadata' => [
                            'entitlement' => 'community_dua_paid',
                            'community_dua_id' => (string) $this->dua->id,
                        ],
                    ],
                ],
            ]);
        }
    });

    $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test'])
        ->assertNoContent();

    $this->post(route('stripe.webhook'), [], ['Stripe-Signature' => 'test'])
        ->assertNoContent();

    expect($dua->fresh()->status)->toBe(CommunityDuaStatus::Active);
    expect(StripePayment::query()->where('stripe_checkout_session_id', 'cs_webhook_community')->count())->toBe(1);
});

test('community duas appear only when pilgrim has completed all personal duas', function () {
    [$caughtUpOwner, $caughtUpList] = createCaughtUpPilgrim();
    [$pendingOwner, $pendingList] = createCaughtUpPilgrim(withPending: true);

    $communityDua = CommunityDua::factory()->free()->create([
        'content' => 'Community dua for caught up pilgrims.',
    ]);

    $this->actingAs($caughtUpOwner)
        ->get(route('dashboard.lists.show', $caughtUpList))
        ->assertOk()
        ->assertSee('Community dua for caught up pilgrims.');

    $this->actingAs($pendingOwner)
        ->get(route('dashboard.lists.show', $pendingList))
        ->assertOk()
        ->assertDontSee('Community dua for caught up pilgrims.');

    expect($communityDua)->not->toBeNull();
});

test('community duas are hidden when list has no personal submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    CommunityDua::factory()->free()->create([
        'content' => 'Should not appear without personal submissions.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertDontSee('Should not appear without personal submissions.');
});

test('queue rotation prefers paid then free duas', function () {
    $user = User::factory()->create();
    $paid = CommunityDua::factory()->paid()->create(['content' => 'Paid rotation dua']);
    $free = CommunityDua::factory()->free()->create(['content' => 'Free rotation dua']);

    $queue = app(CommunityDuaQueueService::class);

    expect($queue->resolveForUser($user)?->id)->toBe($paid->id);

    $queue->recordSkip($user, $paid);
    CommunityDuaQueueState::query()->where('user_id', $user->id)->update(['current_community_dua_id' => null]);

    expect($queue->resolveForUser($user)?->id)->toBe($free->id);
});

test('skipped and completed community duas are excluded from the queue', function () {
    [$owner, $duaList] = createCaughtUpPilgrim();

    $skipped = CommunityDua::factory()->free()->create(['content' => 'Skipped community dua']);
    $completed = CommunityDua::factory()->free()->create(['content' => 'Completed community dua']);
    $available = CommunityDua::factory()->free()->create(['content' => 'Available community dua']);

    CommunityDuaSkip::query()->create([
        'community_dua_id' => $skipped->id,
        'user_id' => $owner->id,
    ]);

    CommunityDuaCompletion::query()->create([
        'community_dua_id' => $completed->id,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Available community dua')
        ->assertDontSee('Skipped community dua')
        ->assertDontSee('Completed community dua');
});

test('pilgrim can complete skip and report community duas', function () {
    [$owner, $duaList] = createCaughtUpPilgrim();

    $first = CommunityDua::factory()->free()->create(['content' => 'First community dua']);
    $second = CommunityDua::factory()->free()->create(['content' => 'Second community dua']);

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.skip', [$duaList, $first]))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('community_dua_skips', [
        'community_dua_id' => $first->id,
        'user_id' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Second community dua');

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.complete', [$duaList, $second]))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($second->fresh())
        ->status->toBe(CommunityDuaStatus::Completed)
        ->completion_count->toBe(1)
        ->is_visible->toBeFalse();

    $reportTarget = CommunityDua::factory()->free()->create(['content' => 'Report target dua']);

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.report', [$duaList, $reportTarget]), [
            'report_reason' => 'spam',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');
});

test('pilgrim cannot complete the same community dua twice', function () {
    [$owner, $duaList] = createCaughtUpPilgrim();

    $dua = CommunityDua::factory()->paid()->create([
        'completion_count' => 0,
        'required_completions' => 20,
    ]);

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.complete', [$duaList, $dua]))
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.complete', [$duaList, $dua]))
        ->assertRedirect()
        ->assertSessionHasErrors('community_dua');

    expect($dua->fresh()->completion_count)->toBe(1);
    expect(CommunityDuaCompletion::query()->where('community_dua_id', $dua->id)->count())->toBe(1);
});

test('community dua completion sends email notification to submitter', function () {
    Notification::fake();

    [$owner, $duaList] = createCaughtUpPilgrim();
    $dua = CommunityDua::factory()->free()->create(['email' => 'submitter@example.com']);

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.complete', [$duaList, $dua]))
        ->assertRedirect();

    Notification::assertSentOnDemand(
        CommunityDuaCompletedNotification::class,
        function ($notification, $channels, $notifiable) {
            return ($notifiable->routes['mail'] ?? null) === 'submitter@example.com';
        },
    );
});

test('reporting a community dua hides it and reassigns pilgrims', function () {
    [$owner, $duaList] = createCaughtUpPilgrim();

    $reported = CommunityDua::factory()->free()->create(['content' => 'Report me']);
    $fallback = CommunityDua::factory()->free()->create(['content' => 'Fallback dua']);

    CommunityDuaQueueState::query()->create([
        'user_id' => $owner->id,
        'showing_type' => 'free',
        'pattern' => 0,
        'current_community_dua_id' => $reported->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('dashboard.community-duas.report', [$duaList, $reported]), [
            'report_reason' => 'spam',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($reported->fresh())
        ->status->toBe(CommunityDuaStatus::Reported)
        ->is_visible->toBeFalse()
        ->report_reason->toBe('spam');

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Fallback dua')
        ->assertDontSee('Report me');
});

test('admin can view read-only community duas in filament', function () {
    $admin = User::factory()->admin()->create();

    CommunityDua::factory()->free()->create([
        'first_name' => 'Filament',
        'last_name' => 'Admin',
    ]);

    $this->actingAs($admin)
        ->get('/admin/community-duas')
        ->assertOk()
        ->assertSee('Filament');
});
