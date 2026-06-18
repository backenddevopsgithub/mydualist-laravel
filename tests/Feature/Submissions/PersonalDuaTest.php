<?php

use App\Enums\DuaSubmissionStatus;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\LegacyImport\Submissions\SubmissionLockReconciliationService;

test('list owner can add a personal dua from the submissions page', function () {
    $owner = User::factory()->create([
        'first_name' => 'Arsalan',
        'last_name' => 'Hajj',
        'email' => 'owner@example.com',
    ]);
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('Add Personal Dua')
        ->assertSee('Keep all your duas in one place', false);

    $this->actingAs($owner)
        ->post(route('dashboard.lists.personal-duas.store', $duaList), [
            'content' => 'Please make dua for my parents.',
        ])
        ->assertRedirect()
        ->assertSessionHas('status', 'Personal dua added.');

    $submission = DuaSubmission::query()->firstOrFail();

    expect($submission->dua_list_id)->toBe($duaList->id)
        ->and($submission->user_id)->toBe($owner->id)
        ->and($submission->is_personal_dua)->toBeTrue()
        ->and($submission->displayName())->toBe('Arsalan Hajj')
        ->and($submission->content)->toBe('Please make dua for my parents.')
        ->and($submission->status)->toBe(DuaSubmissionStatus::Pending);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', $duaList))
        ->assertOk()
        ->assertSee('parents.')
        ->assertSee('• Personal Dua');
});

test('personal dua validation rejects empty submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->from(route('dashboard.lists.show', $duaList))
        ->post(route('dashboard.lists.personal-duas.store', $duaList), [
            'content' => '',
        ])
        ->assertRedirect(route('dashboard.lists.show', $duaList))
        ->assertSessionHasErrors('content');

    expect(DuaSubmission::query()->count())->toBe(0);
});

test('personal duas support the same status transitions as regular submissions', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);
    $submission = DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $duaList->id,
        'user_id' => $owner->id,
        'content' => 'Please make dua for my family.',
    ]);

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.complete', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Completed);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'status' => DuaSubmissionStatus::Completed->value]))
        ->assertOk()
        ->assertSee('family.')
        ->assertSee('• Personal Dua');

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.undo', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Pending);

    $this->actingAs($owner)
        ->patch(route('dashboard.submissions.hide', [$duaList, $submission]))
        ->assertRedirect();

    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Hidden);
});

test('personal duas remain visible to free plan owners beyond the submission limit', function () {
    $owner = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    DuaSubmission::factory()->count(30)->create(['dua_list_id' => $duaList->id]);
    app(SubmissionLockReconciliationService::class)->reconcile(false);

    $personalDua = DuaSubmission::factory()->personal()->create([
        'dua_list_id' => $duaList->id,
        'user_id' => $owner->id,
        'content' => 'My personal dua beyond the free limit.',
    ]);

    $this->actingAs($owner)
        ->get(route('dashboard.lists.show', ['duaList' => $duaList, 'page' => 3]))
        ->assertOk()
        ->assertSee('limit.')
        ->assertSee('• Personal Dua');
});

test('non owners cannot add personal duas to someone elses list', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($otherUser)
        ->post(route('dashboard.lists.personal-duas.store', $duaList), [
            'content' => 'Please make dua for me.',
        ])
        ->assertForbidden();

    expect(DuaSubmission::query()->count())->toBe(0);
});
