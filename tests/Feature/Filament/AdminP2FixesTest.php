<?php

use App\Filament\Pages\Analytics\CategoryAnalytics;
use App\Filament\Pages\Analytics\DuaListAnalytics;
use App\Filament\Pages\Analytics\SubmissionAnalytics;
use App\Filament\Resources\DuaListResource;
use App\Filament\Resources\DuaListResource\Pages\ListDuaLists;
use App\Filament\Resources\DuaSubmissionResource;
use App\Filament\Resources\DuaSubmissionResource\Pages\ListDuaSubmissions;
use App\Filament\Resources\StripePaymentResource;
use App\Models\DuaList;
use App\Models\User;
use Livewire\Livewire;

test('stripe payment resource is hidden from filament navigation', function () {
    expect(StripePaymentResource::shouldRegisterNavigation())->toBeFalse();
});

test('legacy stripe payments page remains accessible to admins', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/stripe-payments')
        ->assertOk()
        ->assertSee('Historical legacy Stripe Checkout sessions only');
});

test('dua list and submission resources expose analytics cross links', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(ListDuaLists::class)
        ->assertSee('View analytics');

    Livewire::test(ListDuaSubmissions::class)
        ->assertSee('View analytics');
});

test('analytics pages expose manage cross links', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(DuaListAnalytics::class)
        ->assertSee('Manage lists');

    Livewire::test(SubmissionAnalytics::class)
        ->assertSee('Moderate submissions');

    Livewire::test(CategoryAnalytics::class)
        ->assertSee('Manage lists')
        ->assertSee('Counts all lists by category');
});

test('category analytics page renders aggregate rows without record key errors', function () {
    $admin = User::factory()->admin()->create();
    DuaList::factory()->count(2)->create(['occasion' => 'wedding']);
    DuaList::factory()->create(['occasion' => 'funeral']);

    $this->actingAs($admin);

    Livewire::test(CategoryAnalytics::class)
        ->call('loadMetrics')
        ->assertSuccessful()
        ->assertSee('Wedding')
        ->assertSee('Funeral');
});
