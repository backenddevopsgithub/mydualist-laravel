<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;

test('public visitor can submit a dua request to an open list', function () {
    $owner = User::factory()->create(['first_name' => 'Arsalan', 'last_name' => 'Test']);
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'slug' => 'arsalan-hajj-1001',
        'title' => 'Hajj 2027',
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->get(route('dua-lists.public', $duaList))
        ->assertOk()
        ->assertSee('Submit a dua request')
        ->assertSee('Submit anonymously');

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'content' => 'Please make dua for my family.',
        'note' => 'This means a lot.',
    ])->assertRedirect(route('dua-lists.public', $duaList));

    $submission = DuaSubmission::query()->firstOrFail();

    expect($submission->dua_list_id)->toBe($duaList->id)
        ->and($submission->displayName())->toBe('Amina Khan')
        ->and($submission->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($submission->note)->toBe('This means a lot.');
});

test('public submission supports anonymous requests and validates content', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('content');

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'is_anonymous' => '1',
        'first_name' => 'Hidden',
        'last_name' => 'Person',
        'content' => 'Please remember me in your duas.',
    ])->assertRedirect(route('dua-lists.public', $duaList));

    $submission = DuaSubmission::query()->firstOrFail();

    expect($submission->is_anonymous)->toBeTrue()
        ->and($submission->first_name)->toBeNull()
        ->and($submission->displayName())->toBe('Anonymous');
});

test('public visitor can submit multiple dua requests in one form', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->get(route('dua-lists.public', $duaList))
        ->assertOk()
        ->assertSee('+ Add Another Dua')
        ->assertSee('Add up to 35 duas');

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'duas' => [
            'Please make dua for my family.',
            'Please make dua for ease in my exams.',
            'Please make dua for our health.',
        ],
    ])->assertRedirect(route('dua-lists.public', $duaList));

    expect(DuaSubmission::query()->where('dua_list_id', $duaList->id)->count())->toBe(3);

    $this->assertDatabaseHas('dua_submissions', [
        'dua_list_id' => $duaList->id,
        'email' => 'amina@example.com',
        'content' => 'Please make dua for ease in my exams.',
    ]);
});

test('public submission validates maximum batch size', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'duas' => array_fill(0, 36, 'Please make dua for us.'),
        ])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('duas');
});

test('closed archived and expired lists reject public submissions gracefully', function () {
    $archived = DuaList::factory()->create([
        'status' => DuaList::STATUS_ARCHIVED,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);
    $expired = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->subDay(),
        'published_at' => now(),
    ]);

    $this->get(route('dua-lists.public', $archived))
        ->assertOk()
        ->assertSee('Submissions Closed')
        ->assertSee('is not accepting any more duas');

    $this->from(route('dua-lists.public', $archived))
        ->post(route('dua-lists.submissions.store', $archived), [
            'content' => 'Please make dua.',
        ])
        ->assertRedirect(route('dua-lists.public', $archived))
        ->assertSessionHasErrors('content');

    $this->from(route('dua-lists.public', $expired))
        ->post(route('dua-lists.submissions.store', $expired), [
            'content' => 'Please make dua.',
        ])
        ->assertRedirect(route('dua-lists.public', $expired))
        ->assertSessionHasErrors('content');
});

test('per email submission limit is enforced per list', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    DuaSubmission::factory()->count(35)->create([
        'dua_list_id' => $duaList->id,
        'email' => 'same@example.com',
    ]);

    $this->from(route('dua-lists.public', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'email' => 'same@example.com',
            'content' => 'A fourth dua request.',
        ])
        ->assertRedirect(route('dua-lists.public', $duaList))
        ->assertSessionHasErrors('email');
});

test('owner workspace supports filtering search pagination and status transitions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Amina',
        'email' => 'amina@example.com',
        'content' => 'Please make dua for my exams.',
    ]);

    DuaSubmission::factory()->count(16)->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'search' => 'exams']))
        ->assertOk()
        ->assertSee('Amina')
        ->assertSee('Please make dua for my exams.');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Completed)
        ->and($submission->completed_at)->not->toBeNull();

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.undo', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($submission->completed_at)->toBeNull();

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.hide', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Hidden)
        ->and($submission->hidden_at)->not->toBeNull();

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Hidden->value]))
        ->assertOk()
        ->assertSee('Amina');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.unhide', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Pending);

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.archive', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Archived)
        ->and($submission->archived_at)->not->toBeNull();

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.report', [$duaList, $submission]))
        ->assertSessionHasErrors('report_reason');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.report', [$duaList, $submission]), [
            'report_reason' => 'spam',
        ])
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Reported)
        ->and($submission->reported_at)->not->toBeNull()
        ->and($submission->report_reason)->toBe('spam');
});

test('users cannot manage submissions for lists they do not own', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create();
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($user)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertForbidden();
});

test('dashboard and public progress counts update after completion', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);
    $submission = DuaSubmission::factory()->create(['dua_list_id' => $duaList->id]);

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1')
        ->assertSee('100%');

    $this->get(route('dua-lists.public', $duaList))
        ->assertOk()
        ->assertSee('1 completed')
        ->assertSee('1 total')
        ->assertSee('100%');
});
