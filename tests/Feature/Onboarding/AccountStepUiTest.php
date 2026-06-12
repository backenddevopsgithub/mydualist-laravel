<?php

test('account step renders alpine submit gating and wise button classes', function () {
    $response = $this->get('/create-list/account');

    $response->assertOk()
        ->assertSee('Create Your Account')
        ->assertSee('Get started', false)
        ->assertSee('x-data', false)
        ->assertSee('canSubmit', false)
        ->assertSee('x-bind:disabled="! canSubmit"', false)
        ->assertSee('x-model="firstName"', false)
        ->assertSee('x-model="lastName"', false)
        ->assertSee('x-model="email"', false)
        ->assertSee('x-model="password"', false)
        ->assertSee('x-model="passwordConfirmation"', false)
        ->assertSee('x-model="gender"', false)
        ->assertSee('x-model="terms"', false)
        ->assertSee('ui-btn--primary', false)
        ->assertSee('disabled', false);
});

test('account step shows validation errors and keeps submit gating markup', function () {
    $this->from('/create-list/account')
        ->post('/create-list/account', [])
        ->assertRedirect('/create-list/account')
        ->assertSessionHasErrors(['first_name', 'last_name', 'gender', 'email', 'password', 'terms']);

    $this->get('/create-list/account')
        ->assertOk()
        ->assertSee('ui-input--error', false)
        ->assertSee('x-bind:disabled="! canSubmit"', false);
});
