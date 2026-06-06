<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('authenticated user can view upgraded dashboard experience', function () {
    $user = User::factory()->create([
        'name' => 'Arsalan Test',
        'first_name' => 'Arsalan',
        'last_name' => 'Test',
    ]);

    DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Hajj 2027',
        'slug' => 'arsalan-hajj-1001',
        'occasion' => 'hajj',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('My Lists')
        ->assertSee('Active Lists')
        ->assertSee('Archived Lists')
        ->assertSee('Total Submissions')
        ->assertSee('Completed Duas')
        ->assertSee('Hajj 2027')
        ->assertSee('Copy')
        ->assertSee('Dashboard')
        ->assertSee('Profile');
});

test('user can archive restore edit and delete owned lists', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Umrah 2027',
        'slug' => 'user-umrah-1002',
        'status' => DuaList::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->patch(route('dashboard.lists.archive', $duaList))
        ->assertRedirect(route('dashboard.archived'));

    expect($duaList->refresh()->status)->toBe(DuaList::STATUS_ARCHIVED);

    $this->actingAs($user)
        ->get(route('dashboard.archived'))
        ->assertOk()
        ->assertSee('Umrah 2027')
        ->assertSee('Restore');

    $this->actingAs($user)
        ->patch(route('dashboard.lists.restore', $duaList))
        ->assertRedirect(route('dashboard'));

    expect($duaList->refresh()->status)->toBe(DuaList::STATUS_ACTIVE);

    $this->actingAs($user)
        ->patch(route('dashboard.lists.update', $duaList), [
            'title' => 'Updated Umrah',
            'occasion' => 'umrah',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ])
        ->assertRedirect(route('dashboard'));

    expect($duaList->refresh()->title)->toBe('Updated Umrah');

    $this->actingAs($user)
        ->delete(route('dashboard.lists.destroy', $duaList))
        ->assertRedirect(route('dashboard'));

    expect(DuaList::query()->whereKey($duaList->id)->exists())->toBeFalse();
});

test('users cannot manage lists they do not own', function () {
    $user = User::factory()->create();
    $otherList = DuaList::factory()->create();

    $this->actingAs($user)
        ->patch(route('dashboard.lists.archive', $otherList))
        ->assertForbidden();
});

test('profile can be updated password can change and user can logout', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
        'email' => 'old@example.com',
    ]);

    $this->actingAs($user)
        ->patch(route('dashboard.profile.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('dashboard.profile'));

    expect($user->refresh()->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');

    $this->actingAs($user)
        ->patch(route('dashboard.profile.password'), [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('dashboard.profile'));

    expect(Hash::check('NewPassword123!', $user->refresh()->password))->toBeTrue();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('upgrade and my submissions foundations render', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => User::factory()->create()->id]);
    DuaSubmission::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $duaList->id,
        'content' => 'Please remember my family in your duas.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.upgrade'))
        ->assertOk()
        ->assertSee('Upgrade Plan')
        ->assertSee('Current Plan: Free')
        ->assertSee('Payments Coming Soon');

    $this->actingAs($user)
        ->get(route('dashboard.submissions'))
        ->assertOk()
        ->assertSee('My Submissions')
        ->assertSee('Please remember my family in your duas.');
});

test('public dua list renders from root slug and old list route redirects', function () {
    $user = User::factory()->create([
        'name' => 'Arsalan Test',
        'first_name' => 'Arsalan',
        'last_name' => 'Test',
    ]);

    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Hajj 2027',
        'slug' => 'arsalan-hajj-1001',
        'occasion' => 'hajj',
    ]);

    $this->get('/'.$duaList->slug)
        ->assertOk()
        ->assertSee('Hajj 2027')
        ->assertSee('Arsalan Test is collecting dua requests')
        ->assertSee('Submit a Dua Request')
        ->assertSee('Copy Share Link');

    $this->get('/lists/'.$duaList->slug)
        ->assertRedirect(route('dua-lists.public', $duaList));
});
