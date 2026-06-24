<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

test('list submissions page wires ajax completion toggles instead of post forms', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $completeUrl = route('dashboard.submissions.complete', [$duaList, $submission]);
    $undoUrl = route('dashboard.submissions.undo', [$duaList, $submission]);

    $response = $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('data-list-submission-card', false)
        ->assertSee('data-submission-toggle="complete"', false)
        ->assertSee('data-submission-toggle="undo"', false)
        ->assertSee('data-complete-url="'.$completeUrl.'"', false)
        ->assertSee('data-undo-url="'.$undoUrl.'"', false)
        ->assertSee('name="csrf-token"', false);

    $html = $response->getContent();

    expect($html)->not->toContain('<form method="POST" action="'.$completeUrl.'"');
    expect($html)->not->toContain('<form method="POST" action="'.$undoUrl.'"');
});

test('list submissions page keeps ajax completion wiring with infinite scroll', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('data-list-submission-card', false)
        ->assertSee('data-submission-toggle="complete"', false)
        ->assertSee('data-submissions-scroll-sentinel', false)
        ->assertDontSee('data-submissions-pagination', false);
});

test('submission completion returns json for ajax requests', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertOk()
        ->assertJsonPath('data.status', DuaSubmissionStatus::Completed->value)
        ->assertJsonStructure(['meta' => ['status_counts']]);

    $this->actingAs($owner)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->patch(route('dashboard.submissions.undo', [$duaList, $submission]))
        ->assertOk()
        ->assertJsonPath('data.status', DuaSubmissionStatus::Pending->value)
        ->assertJsonStructure(['meta' => ['status_counts']]);
});

test('list submissions partial can be fetched for tab switching', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    DuaSubmission::factory()->create(['dua_list_id' => $duaList->id, 'status' => DuaSubmissionStatus::Completed]);

    $this->actingAs($owner)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'X-List-Submissions-Partial' => '1',
        ])
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Completed->value]))
        ->assertOk()
        ->assertSee('data-list-submission-card', false)
        ->assertSee('data-submission-toggle="undo"', false)
        ->assertDontSee('<!DOCTYPE html>', false);
});

test('submission completion without ajax headers redirects back for legacy clients', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->from(route('dashboard.lists.show', $duaList))
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertRedirect(route('dashboard.lists.show', $duaList));
});
