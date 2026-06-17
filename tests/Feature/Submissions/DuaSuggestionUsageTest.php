<?php

use App\Domains\Cms\Services\DuaSuggestionService;
use App\Models\DuaList;
use App\Models\DuaSuggestion;

test('public list page exposes dua suggestions picker for open lists', function () {
    $duaList = DuaList::factory()->create([
        'slug' => 'suggestions-picker-list',
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $this->get(route('cms.show', $duaList))
        ->assertOk()
        ->assertSee('Suggestions', false)
        ->assertSee('x-data="publicSubmissionForm(', false)
        ->assertSee('suggestions-picker-list', false);
});

test('public submission accepts optional suggestion ids', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $suggestion = DuaSuggestion::factory()->global()->create([
        'used_count' => 2,
    ]);

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina-suggestions@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => ['Please make dua for my family.'],
        'suggestion_ids' => [$suggestion->id],
    ])->assertRedirect();

    expect($suggestion->fresh()->used_count)->toBe(3);
});

test('public submission api increments used_count for submitted suggestion ids', function () {
    $duaList = DuaList::factory()->create(['slug' => 'api-suggestion-usage-list']);
    $suggestion = DuaSuggestion::factory()->global()->create([
        'used_count' => 4,
    ]);

    $this->postJson('/api/v1/public/lists/api-suggestion-usage-list/submissions', [
        'first_name' => 'Guest',
        'last_name' => 'User',
        'email' => 'guest-suggestions@example.com',
        'gender' => 'male',
        'terms' => '1',
        'content' => 'Please make dua for my family.',
        'suggestion_ids' => [$suggestion->id],
    ])->assertCreated();

    expect($suggestion->fresh()->used_count)->toBe(5);
});

test('duplicate suggestion ids in one submission only increment used_count once', function () {
    $duaList = DuaList::factory()->create([
        'status' => DuaList::STATUS_ACTIVE,
        'end_date' => now()->addMonth(),
        'published_at' => now(),
    ]);

    $suggestion = DuaSuggestion::factory()->global()->create([
        'used_count' => 7,
    ]);

    $this->post(route('dua-lists.submissions.store', $duaList), [
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'duplicate-suggestions@example.com',
        'gender' => 'female',
        'terms' => '1',
        'duas' => ['Please make dua for my family.'],
        'suggestion_ids' => [$suggestion->id, $suggestion->id],
    ])->assertRedirect();

    expect($suggestion->fresh()->used_count)->toBe(8);
});

test('dua suggestion service increments used counts in bulk', function () {
    $first = DuaSuggestion::factory()->global()->create(['used_count' => 1]);
    $second = DuaSuggestion::factory()->global()->create(['used_count' => 3]);

    app(DuaSuggestionService::class)->incrementUsedCounts([
        $first->id,
        $second->id,
        $second->id,
    ]);

    expect($first->fresh()->used_count)->toBe(2)
        ->and($second->fresh()->used_count)->toBe(4);
});
