<?php

use App\Models\User;

test('admin cannot update other users through policy used by filament', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    expect($admin->can('update', $admin))->toBeTrue()
        ->and($admin->can('update', $other))->toBeFalse();
});

test('admin cannot open filament edit page for another user', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->get('/admin/users/'.$other->id.'/edit')
        ->assertForbidden();
});
