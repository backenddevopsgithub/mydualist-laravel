<?php

use App\Models\User;
use Filament\Pages\Auth\Login;
use Livewire\Livewire;

test('filament login page is accessible', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    expect($response->getContent())->toStartWith('<!DOCTYPE html>');
    expect($response->getContent())->not->toContain('Deprecated');
});

test('filament login authenticates via livewire session guard', function () {
    $user = User::factory()->admin()->create([
        'email' => 'admin@example.com',
        'password' => 'Password123!',
    ]);

    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    $this->assertAuthenticatedAs($user, 'web');
});

test('native post to filament login route is not supported', function () {
    $this->post('/admin/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertStatus(405);
});
