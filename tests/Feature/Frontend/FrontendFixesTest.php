<?php

use App\Models\User;

test('guest users can access the homepage', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('The easiest way to collect dua requests');
});

test('marketing header shows submit community dua for guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Submit Community Dua')
        ->assertSee(route('community-dua.create'), false);
});

test('marketing header shows submit community dua for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('blogs.index'))
        ->assertOk()
        ->assertSee('Submit Community Dua')
        ->assertSee(route('community-dua.create'), false);
});

test('community dua submission page is accessible to guests and authenticated users', function () {
    $this->get(route('community-dua.create'))
        ->assertOk()
        ->assertSee('Submit a Community Dua');

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('community-dua.create'))
        ->assertOk()
        ->assertSee('Submit a Community Dua');
});
