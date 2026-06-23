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

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Your details')
        ->assertSee('Submit Dua Requests');

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'gender' => 'female',
        'terms' => '1',
        'content' => 'Please make dua for my family.',
    ])->assertRedirect(route('cms.show', $duaList).'#submit-dua');

    $submission = DuaSubmission::query()->firstOrFail();

    expect($submission->dua_list_id)->toBe($duaList->id)
        ->and($submission->displayName())->toBe('Amina Khan')
        ->and($submission->status)->toBe(DuaSubmissionStatus::Pending)
        ->and($submission->content)->toBe('Please make dua for my family.');
});

test('public submission supports validated identity fields and validates content', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->from(route('cms.show', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [])
        ->assertRedirect(route('cms.show', $duaList))
        ->assertSessionHasErrors(['content', 'gender', 'first_name', 'last_name', 'email', 'terms']);
});

test('public visitor can submit multiple dua requests in one form', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('+ Add Another Dua')
        ->assertSee('Add up to 35 duas');

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => [
            'Please make dua for my family.',
            'Please make dua for ease in my exams.',
            'Please make dua for our health.',
        ],
    ])->assertRedirect(route('cms.show', $duaList).'#submit-dua');

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

    $this->from(route('cms.show', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'duas' => array_fill(0, 36, 'Please make dua for us.'),
        ])
        ->assertRedirect(route('cms.show', $duaList))
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

    $this->get(route('cms.show', $archived))
        ->assertOk()
        ->assertSee('Submissions Closed')
        ->assertSee('is no longer accepting dua requests');

    $this->from(route('cms.show', $archived))
        ->post(route('dua-lists.submissions.store', $archived), [
            'first_name' => 'Amina',
            'last_name' => 'Khan',
            'email' => 'amina@example.com',
            'gender' => 'female',
            'terms' => '1',
            'duas' => ['Please make dua.'],
        ])
        ->assertRedirect(route('cms.show', $archived))
        ->assertSessionHasErrors('content');

    $this->from(route('cms.show', $expired))
        ->post(route('dua-lists.submissions.store', $expired), [
            'first_name' => 'Amina',
            'last_name' => 'Khan',
            'email' => 'amina@example.com',
            'gender' => 'female',
            'terms' => '1',
            'duas' => ['Please make dua.'],
        ])
        ->assertRedirect(route('cms.show', $expired))
        ->assertSessionHasErrors('content');
});

test('turning a list off blocks public submissions with valid form data', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $owner->id,
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->actingAs($owner)
        ->patch(route('dashboard.lists.archive', $duaList))
        ->assertRedirect(route('dashboard.archived'));

    expect($duaList->refresh()->isArchived())->toBeTrue();

    $response = $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Submissions Closed')
        ->assertDontSee('Submit Dua Requests');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->from(route('cms.show', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'first_name' => 'Amina',
            'last_name' => 'Khan',
            'email' => 'amina@example.com',
            'gender' => 'female',
            'terms' => '1',
            'duas' => ['Please make dua for my family.'],
        ])
        ->assertRedirect(route('cms.show', $duaList))
        ->assertSessionHasErrors('content');

    expect(DuaSubmission::query()->where('dua_list_id', $duaList->id)->exists())->toBeFalse();
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

    $this->from(route('cms.show', $duaList))
        ->post(route('dua-lists.submissions.store', $duaList), [
            'first_name' => 'Same',
            'last_name' => 'Person',
            'email' => 'same@example.com',
            'gender' => 'female',
            'terms' => '1',
            'content' => 'A fourth dua request.',
        ])
        ->assertRedirect(route('cms.show', $duaList))
        ->assertSessionHasErrors('email');
});

test('owner workspace supports visible tabs pagination and status transitions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    DuaSubmission::factory()->count(14)->create([
        'dua_list_id' => $duaList->id,
        'is_personal_dua' => false,
    ]);

    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'first_name' => 'Amina',
        'email' => 'amina@example.com',
        'content' => 'Please make dua for my exams.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList]))
        ->assertOk()
        ->assertSee('Incomplete Duas')
        ->assertSee('Completed Duas')
        ->assertDontSee('Hidden')
        ->assertDontSee('Archived (')
        ->assertDontSee('Reported')
        ->assertDontSee('Search')
        ->assertSee('Amina')
        ->assertSee('exams.')
        ->assertSee('aria-label="Hide dua"', false)
        ->assertDontSee('amina@example.com')
        ->assertDontSee('Personal Dua')
        ->assertDontSee('>Archive<', false);

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
        ->get(route('dashboard.lists.show', ['duaList' => $duaList]))
        ->assertOk()
        ->assertSee('Incomplete Duas')
        ->assertSee('See Hidden Duas (1)');

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Hidden->value]))
        ->assertOk()
        ->assertSee('Hidden Duas')
        ->assertSee('aria-label="Unhide dua"', false)
        ->assertSee('Amina');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.unhide', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Pending);

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

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('1 completed')
        ->assertSee('1 total')
        ->assertSee('100%');
});
