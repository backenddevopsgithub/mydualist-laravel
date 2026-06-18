<?php

use App\Domains\Billing\Services\EntitlementGrantManagementService;
use App\Domains\Billing\Services\EntitlementGrantService;
use App\Enums\EntitlementKey;
use App\Enums\EntitlementProductType;
use App\Filament\Resources\EntitlementGrantResource\Pages\CreateEntitlementGrant;
use App\Filament\Resources\EntitlementGrantResource\Pages\ListEntitlementGrants;
use App\Models\DuaList;
use App\Models\EntitlementGrant;
use App\Models\User;
use Livewire\Livewire;

test('entitlement grants resource is restricted to active admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->get('/admin/entitlement-grants')->assertRedirect('/admin/login');

    $this->actingAs($user)
        ->get('/admin/entitlement-grants')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/entitlement-grants')
        ->assertOk();
});

test('admin can create an extra list entitlement grant', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(CreateEntitlementGrant::class)
        ->fillForm([
            'user_id' => $user->id,
            'product' => EntitlementProductType::ExtraList->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin/entitlement-grants');

    $grant = EntitlementGrant::query()->where('user_id', $user->id)->first();

    expect($grant)->not->toBeNull()
        ->and($grant->entitlement_key)->toBe(EntitlementKey::UserExtraListSlot)
        ->and($grant->quantity)->toBe(1)
        ->and($grant->is_stackable)->toBeTrue()
        ->and($grant->metadata['granted_by'])->toBe($admin->id)
        ->and(app(EntitlementGrantService::class)->quantity($user, EntitlementKey::UserExtraListSlot))->toBe(1);
});

test('admin can create a 25-pack grant for a user list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->actingAs($admin);

    Livewire::test(CreateEntitlementGrant::class)
        ->fillForm([
            'user_id' => $user->id,
            'product' => EntitlementProductType::RequestPack25->value,
            'dua_list_id' => $list->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $grant = EntitlementGrant::query()->where('dua_list_id', $list->id)->first();

    expect($grant?->entitlement_key)->toBe(EntitlementKey::ListVisibleSubmissionPack)
        ->and($grant?->quantity)->toBe((int) config('billing.request_pack_size'));
});

test('admin can revoke an active entitlement grant', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $grant = EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'quantity' => 1,
        'is_stackable' => true,
        'dedupe_key' => 'admin:test-revoke',
        'metadata' => [
            'source' => 'admin',
            'granted_by' => $admin->id,
        ],
    ]);

    $this->actingAs($admin);

    Livewire::test(ListEntitlementGrants::class)
        ->callTableAction('revoke', $grant)
        ->assertNotified();

    expect($grant->fresh()->isActive())->toBeFalse()
        ->and($grant->fresh()->metadata['revoked_by'])->toBe($admin->id)
        ->and(app(EntitlementGrantService::class)->quantity($user, EntitlementKey::UserExtraListSlot))->toBe(0);
});

test('admin can extend entitlement grant expiration', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $newExpiry = now()->addMonths(2);

    $grant = EntitlementGrant::factory()->create([
        'user_id' => $user->id,
        'entitlement_key' => EntitlementKey::UserExtraListSlot,
        'quantity' => 1,
        'is_stackable' => true,
        'dedupe_key' => 'admin:test-extend',
        'expires_at' => now()->addWeek(),
        'metadata' => [
            'source' => 'admin',
            'granted_by' => $admin->id,
        ],
    ]);

    $this->actingAs($admin);

    Livewire::test(ListEntitlementGrants::class)
        ->callTableAction('extendExpiration', $grant, data: [
            'expires_at' => $newExpiry->toDateTimeString(),
        ])
        ->assertNotified();

    expect($grant->fresh()->expires_at?->toDateTimeString())
        ->toBe($newExpiry->toDateTimeString())
        ->and($grant->fresh()->metadata['expiration_extended_by'])->toBe($admin->id);
});

test('non-admin cannot manage entitlement grants through policy', function () {
    $user = User::factory()->create();
    $grant = EntitlementGrant::factory()->create(['user_id' => $user->id]);

    expect($user->can('viewAny', EntitlementGrant::class))->toBeFalse()
        ->and($user->can('create', EntitlementGrant::class))->toBeFalse()
        ->and($user->can('update', $grant))->toBeFalse();
});

test('entitlement grant management service rejects duplicate unique grants', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $service = app(EntitlementGrantManagementService::class);

    $service->createGrant($user, EntitlementProductType::UnlimitedForever, $admin);

    expect(fn () => $service->createGrant($user, EntitlementProductType::UnlimitedForever, $admin))
        ->toThrow(\RuntimeException::class);
});
