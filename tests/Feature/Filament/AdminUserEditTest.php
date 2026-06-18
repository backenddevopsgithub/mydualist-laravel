<?php

use App\Models\User;

test('admin can update other users through policy used by filament', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    expect($admin->can('update', $admin))->toBeTrue()
        ->and($admin->can('update', $other))->toBeTrue();
});

test('non-admin can only update themselves through policy', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect($user->can('update', $user))->toBeTrue()
        ->and($user->can('update', $other))->toBeFalse();
});

test('admin can open filament edit page for another user', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->get('/admin/users/'.$other->id.'/edit')
        ->assertOk();
});

test('non-admin cannot open filament edit page for another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/users/'.$other->id.'/edit')
        ->assertForbidden();
});
