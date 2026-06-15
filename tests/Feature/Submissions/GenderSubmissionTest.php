<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Support\DuaListDisplayOrders;

test('owner submission cards display gender indicators before submitter names', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Ahmed',
        'last_name' => 'Ali',
        'gender' => 'male',
        'content' => 'Please make dua for my exams.',
    ]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Fatima',
        'last_name' => 'Khan',
        'gender' => 'female',
        'content' => 'Please make dua for my family.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSeeInOrder(['Men', 'Ahmed'])
        ->assertSeeInOrder(['Women', 'Fatima']);
});

test('personal duas do not display gender indicators on owner cards', function () {
    $owner = User::factory()->create([
        'first_name' => 'Arsalan',
        'last_name' => 'Hajj',
    ]);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $duaList->id,
        'user_id' => $owner->id,
        'first_name' => 'Arsalan',
        'last_name' => 'Hajj',
        'gender' => 'male',
        'content' => 'Please make dua for my parents.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('• Personal Dua')
        ->assertDontSee('Men')
        ->assertDontSee('Women');
});

test('api submission index exposes gender for regular submissions', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create([
        'user_id' => $user->id,
        'display_order' => DuaListDisplayOrders::GENDER,
    ]);

    $male = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'gender' => 'male',
    ]);
    $female = DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'gender' => 'female',
    ]);
    $personal = DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $list->id,
        'user_id' => $user->id,
        'gender' => 'male',
    ]);

    $response = $this->getJson('/api/v1/lists/'.$list->id.'/submissions?per_page=50')->assertOk();

    $malePayload = collect($response->json('data'))->firstWhere('id', $male->id);
    $femalePayload = collect($response->json('data'))->firstWhere('id', $female->id);
    $personalPayload = collect($response->json('data'))->firstWhere('id', $personal->id);

    expect($malePayload['gender'])->toBe('male')
        ->and($femalePayload['gender'])->toBe('female')
        ->and($personalPayload)->not->toHaveKey('gender');
});
