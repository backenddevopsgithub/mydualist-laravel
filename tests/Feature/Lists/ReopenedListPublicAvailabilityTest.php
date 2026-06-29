<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Support\Carbon;

test('naturally expired active list is closed on the public page', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');

    $owner = User::factory()->create(['first_name' => 'Amina']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'expired-naturally-list',
        'status' => DuaList::STATUS_ACTIVE,
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'published_at' => now()->subMonth(),
    ]);

    Carbon::setTestNow('2026-06-15 12:00:00');

    expect($duaList->refresh()->isActive())->toBeTrue()
        ->and($duaList->isExpired())->toBeTrue()
        ->and($duaList->acceptsSubmissions())->toBeFalse()
        ->and($duaList->dashboardAvailability())->toBe('closed');

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Submissions Closed')
        ->assertSee('is no longer accepting dua requests')
        ->assertDontSee('Submit Dua Requests');
});

test('reopened list agrees between dashboard and public page and accepts submissions', function () {
    Carbon::setTestNow('2026-06-01 12:00:00');

    $owner = User::factory()->create(['first_name' => 'Amina']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'reopened-public-list',
        'title' => 'Reopened Hajj List',
        'status' => DuaList::STATUS_ACTIVE,
        'start_date' => '2026-05-01',
        'end_date' => '2026-05-31',
        'published_at' => now()->subMonth(),
    ]);

    Carbon::setTestNow('2026-06-15 12:00:00');
    $duaList->refresh();

    $this->actingAs($owner)
        ->patch(route('dashboard.lists.archive', $duaList))
        ->assertRedirect(route('dashboard.archived'));

    expect($duaList->refresh()->isArchived())->toBeTrue();

    $this->actingAs($owner)
        ->patch(route('dashboard.lists.update', $duaList), [
            'title' => 'Reopened Hajj List',
            'start_date' => '2026-05-01',
            'end_date' => '2026-08-31',
            'redirect_to' => route('dashboard.lists.show', $duaList),
        ])
        ->assertRedirect(route('dashboard.lists.show', $duaList));

    $duaList->refresh();

    expect($duaList->isActive())->toBeTrue()
        ->and($duaList->isExpired())->toBeFalse()
        ->and($duaList->acceptsSubmissions())->toBeTrue()
        ->and($duaList->dashboardAvailability())->toBe('active');

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('ON');

    $this->getJson('/api/v1/public/lists/reopened-public-list')
        ->assertOk()
        ->assertJsonPath('data.accepts_submissions', true)
        ->assertJsonPath('data.closed_reason', null);

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Submit Dua Requests')
        ->assertDontSee('Submissions Closed');

    $this->from(route('cms.show', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'first_name' => 'Sara',
            'last_name' => 'Ali',
            'email' => 'sara@example.com',
            'gender' => 'female',
            'terms' => '1',
            'duas' => ['Please make dua for my family.'],
        ])
        ->assertRedirect(route('cms.show', $duaList).'#submit-dua');

    expect(DuaSubmission::query()->where('dua_list_id', $duaList->id)->exists())->toBeTrue();
});

test('restoring a reopened archived list clears stale closed state on the public page', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'restore-after-extend-list',
        'status' => DuaList::STATUS_ARCHIVED,
        'start_date' => '2026-05-01',
        'end_date' => '2026-08-31',
        'published_at' => null,
    ]);

    $this->actingAs($owner)
        ->patch(route('dashboard.lists.restore', $duaList), [
            'redirect_to' => route('dashboard.lists.show', $duaList),
        ])
        ->assertRedirect(route('dashboard.lists.show', $duaList));

    $duaList->refresh();

    expect($duaList->isActive())->toBeTrue()
        ->and($duaList->published_at)->not->toBeNull()
        ->and($duaList->acceptsSubmissions())->toBeTrue()
        ->and($duaList->dashboardAvailability())->toBe('active');

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Submit Dua Requests')
        ->assertDontSee('Submissions Closed');
});

test('list remains open through its end date', function () {
    Carbon::setTestNow('2026-06-29 18:30:00');

    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => '2026-06-29',
        'published_at' => now()->subDay(),
    ]);

    expect($duaList->isExpired())->toBeFalse()
        ->and($duaList->acceptsSubmissions())->toBeTrue()
        ->and($duaList->daysRemainingLabel())->toBe('Ends today');
});
