<?php

use App\Domains\Billing\Actions\GrantUserPlanAction;
use App\Domains\Billing\Services\EntitlementGrantService;
use App\Domains\Billing\Services\EntitlementResolverService;
use App\Domains\Billing\Services\PurchaseFulfillmentService;
use App\Enums\BillingProductCode;
use App\Enums\BillingPurchaseStatus;
use App\Enums\EntitlementKey;
use App\Enums\EntitlementProductType;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\BillingPurchase;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed(BillingProductSeeder::class);
});

test('admin can grant extra list plan to user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    ['purchase' => $purchase, 'grant' => $grant] = app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::ExtraList,
        $admin,
    );

    expect($purchase->status)->toBe(BillingPurchaseStatus::Succeeded)
        ->and($purchase->fulfilled_at)->not->toBeNull()
        ->and($purchase->metadata['granted_by'])->toBe($admin->id)
        ->and($purchase->metadata['granted_by_email'])->toBe($admin->email)
        ->and($purchase->metadata['admin_action'])->toBe('grant_plan')
        ->and($purchase->product->code)->toBe(BillingProductCode::AdditionalList->value);

    expect($grant->entitlement_key)->toBe(EntitlementKey::UserExtraListSlot)
        ->and($grant->source_purchase_id)->toBe($purchase->id)
        ->and($grant->quantity)->toBe(1);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveListCapacity($user))
        ->toBe((int) config('billing.default_list_capacity') + 1);
});

test('admin can grant 25-pack plan to user list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    ['purchase' => $purchase, 'grant' => $grant] = app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::RequestPack25,
        $admin,
        $list->id,
    );

    expect($purchase->dua_list_id)->toBe($list->id)
        ->and($purchase->product->code)->toBe(BillingProductCode::RequestPack25->value)
        ->and($purchase->metadata['granted_by'])->toBe($admin->id);

    expect($grant->entitlement_key)->toBe(EntitlementKey::ListVisibleSubmissionPack)
        ->and($grant->dua_list_id)->toBe($list->id)
        ->and($grant->quantity)->toBe((int) config('billing.request_pack_size'));

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveVisibleQuota($user, $list))
        ->toBe((int) config('billing.free_visible_submissions_per_list') + (int) config('billing.request_pack_size'));
});

test('admin can grant unlimited one list plan to user list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    ['purchase' => $purchase, 'grant' => $grant] = app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::UnlimitedOneList,
        $admin,
        $list->id,
    );

    expect($purchase->product->code)->toBe(BillingProductCode::UnlimitedOneList->value)
        ->and($purchase->metadata['granted_by'])->toBe($admin->id);

    expect($grant->entitlement_key)->toBe(EntitlementKey::ListUnlimitedOverride)
        ->and($grant->dua_list_id)->toBe($list->id);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveVisibleQuota($user, $list))
        ->toBe((int) config('billing.unlimited_list_submission_cap'));
});

test('admin can grant unlimited forever plan to user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    ['purchase' => $purchase, 'grant' => $grant] = app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::UnlimitedForever,
        $admin,
    );

    expect($purchase->product->code)->toBe(BillingProductCode::UnlimitedForever->value)
        ->and($purchase->metadata['granted_by'])->toBe($admin->id);

    expect($grant->entitlement_key)->toBe(EntitlementKey::UserUnlimitedForever)
        ->and($grant->source_purchase_id)->toBe($purchase->id);

    $resolver = app(EntitlementResolverService::class);

    expect($resolver->effectiveListCapacity($user))->toBeNull()
        ->and($resolver->hasUnlimitedForever($user))->toBeTrue();
});

test('grant plan rolls back purchase and grant when fulfillment fails', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $fulfillment = Mockery::mock(PurchaseFulfillmentService::class);
    $fulfillment->shouldReceive('fulfill')
        ->once()
        ->andThrow(new RuntimeException('Fulfillment failed'));
    app()->instance(PurchaseFulfillmentService::class, $fulfillment);

    expect(fn () => app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::ExtraList,
        $admin,
    ))->toThrow(RuntimeException::class, 'Fulfillment failed');

    expect(BillingPurchase::query()->count())->toBe(0)
        ->and(EntitlementGrant::query()->count())->toBe(0)
        ->and(app(EntitlementGrantService::class)->quantity($user, EntitlementKey::UserExtraListSlot))->toBe(0);
});

test('admin can grant plan from user resource table action', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('grantPlan', $user, data: [
            'product' => EntitlementProductType::ExtraList->value,
        ])
        ->assertNotified();

    $purchase = BillingPurchase::query()->where('user_id', $user->id)->first();

    expect($purchase)->not->toBeNull()
        ->and($purchase->fulfilled_at)->not->toBeNull()
        ->and($purchase->metadata['granted_by'])->toBe($admin->id)
        ->and(EntitlementGrant::query()->where('source_purchase_id', $purchase->id)->exists())->toBeTrue()
        ->and(app(EntitlementGrantService::class)->quantity($user, EntitlementKey::UserExtraListSlot))->toBe(1);
});

test('grant plan table action requires list for list-scoped products', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->callTableAction('grantPlan', $user, data: [
            'product' => EntitlementProductType::RequestPack25->value,
            'dua_list_id' => $list->id,
        ])
        ->assertNotified();

    expect(EntitlementGrant::query()
        ->where('dua_list_id', $list->id)
        ->where('entitlement_key', EntitlementKey::ListVisibleSubmissionPack)
        ->exists())->toBeTrue();
});

test('user resource exposes grant plan but not grant premium', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('grantPlan', $user)
        ->assertDontSee('Grant Premium');
});
