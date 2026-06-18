<?php

use App\Domains\Billing\Services\EntitlementResolverService;
use App\Enums\DuaSubmissionStatus;
use App\Enums\EntitlementKey;
use App\Models\BillingPurchase;
use App\Models\CommunityDua;
use App\Models\CommunityDuaCompletion;
use App\Models\CommunityDuaQueueState;
use App\Models\CommunityDuaSkip;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\EntitlementGrant;
use App\Models\User;
use App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService;
use App\Services\LegacyImport\Support\LegacyWhatsAppPhoneParser;
use Database\Seeders\BillingProductSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->seed(BillingProductSeeder::class);
});

function importLegacyFoundation(): void
{
    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);
    Artisan::call('migrate:lists', ['--csv' => base_path('tests/Fixtures/legacy-import/lists.csv')]);
}

test('migrate purchases imports orders and grants entitlements', function () {
    importLegacyFoundation();

    Artisan::call('migrate:purchases', ['--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv')]);

    $packPurchase = BillingPurchase::query()->where('wp_order_id', 5001)->first();
    $extraListPurchase = BillingPurchase::query()->where('wp_order_id', 5002)->first();

    expect($packPurchase)->not->toBeNull()
        ->and($packPurchase->fulfilled_at)->not->toBeNull()
        ->and($packPurchase->dua_list_id)->toBe(DuaList::query()->where('wp_post_id', 301)->value('id'));

    $grant = EntitlementGrant::query()->where('source_purchase_id', $packPurchase->id)->first();

    expect($grant)->not->toBeNull()
        ->and($grant->entitlement_key)->toBe(EntitlementKey::ListVisibleSubmissionPack)
        ->and($grant->dedupe_key)->not->toBeNull();

    expect($extraListPurchase)->not->toBeNull()
        ->and(EntitlementGrant::query()->where('source_purchase_id', $extraListPurchase->id)->value('entitlement_key'))
        ->toBe(EntitlementKey::UserExtraListSlot);
});

test('migrate purchases is idempotent on repeated runs', function () {
    importLegacyFoundation();

    $csv = base_path('tests/Fixtures/legacy-import/purchases.csv');

    Artisan::call('migrate:purchases', ['--csv' => $csv]);
    Artisan::call('migrate:purchases', ['--csv' => $csv]);

    expect(BillingPurchase::query()->where('wp_order_id', 5001)->count())->toBe(1)
        ->and(EntitlementGrant::query()->count())->toBe(2);
});

test('migrate purchases dry run does not persist purchases', function () {
    importLegacyFoundation();

    Artisan::call('migrate:purchases', [
        '--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv'),
        '--dry-run' => true,
    ]);

    expect(BillingPurchase::query()->count())->toBe(0);
});

test('legacy whatsapp phone parser splits e164 numbers', function () {
    $parsed = LegacyWhatsAppPhoneParser::parse('+447700900123');

    expect($parsed['whatsapp_country_code'])->toBe('+44')
        ->and($parsed['whatsapp_phone'])->toBe('7700900123')
        ->and($parsed['is_valid'])->toBeTrue();
});

test('migrate submissions imports records with whatsapp and status mapping', function () {
    importLegacyFoundation();
    Artisan::call('migrate:purchases', ['--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv')]);

    Artisan::call('migrate:submissions', ['--csv' => base_path('tests/Fixtures/legacy-import/submissions.csv')]);

    $pending = DuaSubmission::query()->where('wp_post_id', 401)->first();
    $completed = DuaSubmission::query()->where('wp_post_id', 402)->first();
    $personal = DuaSubmission::query()->where('wp_post_id', 403)->first();

    expect($pending)->not->toBeNull()
        ->and($pending->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($pending->is_anonymous)->toBeFalse()
        ->and($pending->whatsapp_country_code)->toBe('+44')
        ->and($pending->whatsapp_verified_at)->not->toBeNull()
        ->and($completed->status)->toBe(DuaSubmissionStatus::Completed)
        ->and($personal->is_personal_dua)->toBeTrue();
});

test('submission lock reconciliation respects entitlements and personal dua exemptions', function () {
    importLegacyFoundation();
    Artisan::call('migrate:purchases', ['--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv')]);
    Artisan::call('migrate:submissions', ['--csv' => base_path('tests/Fixtures/legacy-import/submissions.csv')]);

    $list = DuaList::query()->where('wp_post_id', 301)->firstOrFail();
    $owner = User::query()->where('wp_legacy_id', 42)->firstOrFail();

    $personal = DuaSubmission::query()->where('wp_post_id', 403)->firstOrFail();

    expect($personal->is_locked)->toBeFalse();

    $reconciliation = app(SubmissionLockReconciliationService::class)->reconcile(false);

    expect($reconciliation['lists_processed'])->toBeGreaterThan(0);

    $visible = DuaSubmission::query()
        ->where('dua_list_id', $list->id)
        ->where('is_personal_dua', false)
        ->where(function ($query): void {
            $query->where('is_locked', false)->orWhereNotNull('unlocked_at');
        })
        ->count();

    $quota = app(EntitlementResolverService::class)
        ->effectiveVisibleQuota($owner, $list);

    expect($visible)->toBeLessThanOrEqual($quota);
});

test('migrate submissions dry run does not persist submissions', function () {
    importLegacyFoundation();

    Artisan::call('migrate:submissions', [
        '--csv' => base_path('tests/Fixtures/legacy-import/submissions.csv'),
        '--dry-run' => true,
    ]);

    expect(DuaSubmission::query()->count())->toBe(0);
});

test('migrate community duas imports duas queue state and fulfills paid purchases', function () {
    importLegacyFoundation();
    Artisan::call('migrate:purchases', ['--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv')]);

    Artisan::call('migrate:community-duas', ['--csv' => base_path('tests/Fixtures/legacy-import/community-duas.csv')]);

    $free = CommunityDua::query()->where('wp_post_id', 601)->first();
    $paid = CommunityDua::query()->where('wp_post_id', 602)->first();
    $purchase = BillingPurchase::query()->where('wp_order_id', 5003)->first();
    $subscriber = User::query()->where('wp_legacy_id', 43)->firstOrFail();

    expect($free)->not->toBeNull()
        ->and($free->type->value)->toBe('free')
        ->and($free->required_completions)->toBe(1)
        ->and($paid)->not->toBeNull()
        ->and($paid->completion_count)->toBe(5)
        ->and($purchase->community_dua_id)->toBe($paid->id)
        ->and($purchase->fulfilled_at)->not->toBeNull();

    $queue = CommunityDuaQueueState::query()->where('user_id', $subscriber->id)->first();

    expect($queue)->not->toBeNull()
        ->and($queue->current_community_dua_id)->toBe($free->id)
        ->and(CommunityDuaCompletion::query()->where('user_id', $subscriber->id)->count())->toBe(1)
        ->and(CommunityDuaSkip::query()->where('user_id', $subscriber->id)->count())->toBe(1);
});

test('migrate validate generates a json report with totals', function () {
    importLegacyFoundation();
    Artisan::call('migrate:purchases', ['--csv' => base_path('tests/Fixtures/legacy-import/purchases.csv')]);
    Artisan::call('migrate:submissions', ['--csv' => base_path('tests/Fixtures/legacy-import/submissions.csv')]);
    Artisan::call('migrate:community-duas', ['--csv' => base_path('tests/Fixtures/legacy-import/community-duas.csv')]);

    $reportPath = storage_path('app/testing-legacy-validate-report.json');

    Artisan::call('migrate:validate', ['--report' => $reportPath]);

    $report = json_decode(file_get_contents($reportPath), true);

    expect($report['validation']['totals']['users'])->toBeGreaterThan(0)
        ->and($report['validation']['totals']['lists'])->toBeGreaterThan(0)
        ->and($report['validation']['totals']['submissions'])->toBeGreaterThan(0)
        ->and($report['validation']['totals']['community_duas'])->toBeGreaterThan(0)
        ->and($report['validation']['totals']['purchases'])->toBeGreaterThan(0)
        ->and($report)->toHaveKey('validation');
});
