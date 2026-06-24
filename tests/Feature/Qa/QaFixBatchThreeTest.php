<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

test('list submissions page loads twenty records initially with infinite scroll wiring', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);

    $nextPagePath = parse_url(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 2]), PHP_URL_PATH).'?page=2';

    $response = $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('data-list-submissions-scroll', false)
        ->assertSee('data-submissions-scroll-sentinel', false)
        ->assertSee('data-status-total="25"', false)
        ->assertSee('data-next-page-url="'.$nextPagePath.'"', false)
        ->assertDontSee('data-submissions-pagination', false)
        ->assertDontSee('Showing', false);

    preg_match_all('/data-submission-id="\d+"/', $response->getContent(), $cards);
    expect($cards[0])->toHaveCount(20);
});

test('list submissions infinite scroll returns card item partials with headers', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);

    $response = $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 2]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html+partial',
        ]);

    $response
        ->assertOk()
        ->assertHeader('X-Infinite-Scroll-Has-More', 'false')
        ->assertHeader('X-Infinite-Scroll-Page', '2')
        ->assertHeader('X-Infinite-Scroll-Next-Page', '')
        ->assertDontSee('data-list-submissions-scroll', false)
        ->assertDontSee('<!DOCTYPE html>', false);

    preg_match_all('/data-submission-id="\d+"/', $response->getContent(), $cards);
    expect($cards[0])->toHaveCount(5);
});

test('list submissions tab partial includes scroll root and status total', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    DuaSubmission::factory()->count(3)->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'X-List-Submissions-Partial' => '1',
        ])
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Completed->value]))
        ->assertOk()
        ->assertSee('data-list-submissions-scroll', false)
        ->assertSee('data-status-total="3"', false)
        ->assertSee('data-submission-toggle="undo"', false)
        ->assertDontSee('data-submissions-pagination', false);
});

test('status counts in completion json stay accurate after rapid toggles', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submissions = DuaSubmission::factory()->count(5)->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed,
    ]);

    foreach ($submissions as $submission) {
        $this->actingAs($owner)
            ->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->patch(route('dashboard.submissions.undo', [$duaList, $submission]))
            ->assertOk()
            ->assertJsonPath('data.status', DuaSubmissionStatus::Pending->value);
    }

    $duaList->refresh();

    expect($duaList->pending_submissions_count)->toBe(5)
        ->and($duaList->completed_submissions_count)->toBe(0);

    $this->actingAs($owner)
        ->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])
        ->patch(route('dashboard.submissions.complete', [$duaList, $submissions->first()]))
        ->assertOk()
        ->assertJsonPath('meta.status_counts.pending', 4)
        ->assertJsonPath('meta.status_counts.completed', 1);
});

test('completed tab empty state only renders when there are zero completed submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    DuaSubmission::factory()->count(2)->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Completed->value]))
        ->assertOk()
        ->assertSee('data-submissions-empty', false)
        ->assertSee('countFor(\'completed\')">0</span>', false);

    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Completed,
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Completed->value]))
        ->assertOk()
        ->assertDontSee('data-submissions-empty', false)
        ->assertSee('data-submission-toggle="undo"', false)
        ->assertSee('countFor(\'completed\')">1</span>', false);
});

test('infinite scroll headers use relative next page urls', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);

    $expectedPath = parse_url(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 2]), PHP_URL_PATH).'?page=2';

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 1]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html+partial',
        ])
        ->assertOk()
        ->assertHeader('X-Infinite-Scroll-Has-More', 'true')
        ->assertHeader('X-Infinite-Scroll-Next-Page', $expectedPath);
});

test('infinite scroll load more does not duplicate submission cards', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submissions = DuaSubmission::factory()->count(25)->create(['dua_list_id' => $duaList->id]);

    $firstPageIds = $submissions->take(20)->pluck('id')->all();

    $secondPage = $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 2]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html+partial',
        ])
        ->assertOk()
        ->getContent();

    preg_match_all('/data-submission-id="(\d+)"/', $secondPage, $secondPageMatches);
    $secondPageIds = array_map('intval', $secondPageMatches[1]);

    expect($secondPageIds)->toHaveCount(5)
        ->and(array_intersect($firstPageIds, $secondPageIds))->toBeEmpty();
});
