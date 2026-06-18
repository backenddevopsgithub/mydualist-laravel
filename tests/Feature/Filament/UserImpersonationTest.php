<?php

use App\Domains\Billing\Actions\GrantUserPlanAction;
use App\Enums\BillingProductCode;
use App\Enums\EntitlementProductType;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\ImpersonationLog;
use App\Models\User;
use Database\Seeders\BillingProductSeeder;
use Livewire\Livewire;

test('admin can impersonate a regular user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->get(route('impersonate', $user->id))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    $log = ImpersonationLog::query()->first();

    expect($log)->not->toBeNull()
        ->and($log->impersonator_id)->toBe($admin->id)
        ->and($log->impersonated_user_id)->toBe($user->id)
        ->and($log->started_at)->not->toBeNull()
        ->and($log->ended_at)->toBeNull()
        ->and($log->ip_address)->not->toBeNull();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('You are impersonating')
        ->assertSee('Stop impersonating');
});

test('admin cannot impersonate another admin', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('impersonate', $otherAdmin->id))
        ->assertRedirect();

    $this->assertAuthenticatedAs($admin);
    expect(ImpersonationLog::query()->count())->toBe(0);
});

test('non-admin cannot impersonate users', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->get(route('impersonate', $other->id))
        ->assertForbidden();

    $this->assertAuthenticatedAs($user);
    expect(ImpersonationLog::query()->count())->toBe(0);
});

test('impersonation can be stopped and returns admin to user list', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->get(route('impersonate', $user->id));
    $this->assertAuthenticatedAs($user);

    $this->get(route('impersonate.leave'))
        ->assertRedirect(route('filament.admin.resources.users.index'));

    $this->assertAuthenticatedAs($admin);

    $log = ImpersonationLog::query()->first();

    expect($log?->ended_at)->not->toBeNull();
});

test('sensitive actions are blocked while impersonating', function () {
    $this->seed(BillingProductSeeder::class);

    $admin = User::factory()->admin()->create([
        'password' => 'Password123!',
    ]);
    $user = User::factory()->create([
        'password' => 'Password123!',
    ]);

    $this->actingAs($admin)->get(route('impersonate', $user->id));
    $this->assertAuthenticatedAs($user);

    $this->from(route('dashboard.profile'))
        ->patch(route('dashboard.profile.password'), [
            'current_password' => 'Password123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('dashboard.profile'));

    $this->from(route('dashboard'))
        ->post(route('billing.purchases.start'), [
            'product_code' => BillingProductCode::AdditionalList->value,
        ])
        ->assertRedirect(route('dashboard'));

    expect(fn () => app(GrantUserPlanAction::class)(
        $user,
        EntitlementProductType::ExtraList,
        $admin,
    ))->toThrow(\Illuminate\Http\Exceptions\HttpResponseException::class);
});

test('filament hides sensitive user actions while impersonation session is active', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);
    session([
        config('laravel-impersonate.session_key') => $admin->id,
        config('laravel-impersonate.session_guard') => 'web',
        config('laravel-impersonate.session_guard_using') => 'web',
    ]);

    Livewire::test(ListUsers::class)
        ->assertTableActionHidden('grantPlan', $user)
        ->assertTableActionHidden('impersonate', $user);
});

test('filament user resource shows impersonate action for eligible users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin);

    Livewire::test(ListUsers::class)
        ->assertTableActionVisible('impersonate', $user)
        ->assertTableActionHidden('impersonate', $admin);
});
