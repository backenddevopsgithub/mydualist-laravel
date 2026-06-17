<?php

use App\Models\DuaList;
use App\Models\DuaSuggestion;

test('public list suggestions endpoint does not require authentication', function () {
    $list = DuaList::factory()->create([
        'slug' => 'hajj-suggestions-list',
        'occasion' => 'hajj',
    ]);

    $global = DuaSuggestion::factory()->global()->create([
        'title' => 'Global dua',
        'source_type' => 'general',
        'sort_order' => 1,
    ]);

    $hajj = DuaSuggestion::factory()->create([
        'title' => 'Hajj dua',
        'category' => 'hajj',
        'source_type' => 'quran',
        'sort_order' => 2,
    ]);

    $this->getJson('/api/v1/public/lists/hajj-suggestions-list/suggestions')
        ->assertOk()
        ->assertJsonPath('message', 'List suggestions retrieved.')
        ->assertJsonStructure([
            'data' => [
                'general',
                'quran',
                'sunnah',
            ],
        ])
        ->assertJsonPath('data.general.0.id', $global->id)
        ->assertJsonPath('data.quran.0.id', $hajj->id)
        ->assertJsonPath('data.sunnah', []);
});

test('public list suggestions endpoint returns not found for unknown slug', function () {
    $this->getJson('/api/v1/public/lists/does-not-exist/suggestions')->assertNotFound();
});

test('public list suggestions endpoint filters by list occasion and includes global suggestions', function () {
    $list = DuaList::factory()->create([
        'slug' => 'ramadan-suggestions-list',
        'occasion' => 'ramadan',
    ]);

    $global = DuaSuggestion::factory()->global()->create([
        'title' => 'Applies everywhere',
        'source_type' => 'general',
    ]);

    $matching = DuaSuggestion::factory()->create([
        'title' => 'Ramadan specific',
        'category' => 'ramadan',
        'source_type' => 'general',
    ]);

    $otherOccasion = DuaSuggestion::factory()->create([
        'title' => 'Hajj only',
        'category' => 'hajj',
        'source_type' => 'general',
    ]);

    $hidden = DuaSuggestion::factory()->hidden()->create([
        'title' => 'Hidden suggestion',
        'category' => 'ramadan',
        'source_type' => 'general',
    ]);

    $response = $this->getJson('/api/v1/public/lists/ramadan-suggestions-list/suggestions')
        ->assertOk();

    $generalIds = collect($response->json('data.general'))->pluck('id');

    expect($generalIds)->toContain($global->id, $matching->id)
        ->not->toContain($otherOccasion->id, $hidden->id);
});

test('public list suggestions endpoint groups suggestions by source type', function () {
    DuaList::factory()->create([
        'slug' => 'grouped-suggestions-list',
        'occasion' => 'hajj',
    ]);

    $general = DuaSuggestion::factory()->global()->create([
        'title' => 'General source',
        'source_type' => 'general',
    ]);

    $quran = DuaSuggestion::factory()->global()->create([
        'title' => 'Quran source',
        'source_type' => 'quran',
    ]);

    $sunnah = DuaSuggestion::factory()->global()->create([
        'title' => 'Sunnah source',
        'source_type' => 'sunnah',
    ]);

    $this->getJson('/api/v1/public/lists/grouped-suggestions-list/suggestions')
        ->assertOk()
        ->assertJsonPath('data.general.0.id', $general->id)
        ->assertJsonPath('data.quran.0.id', $quran->id)
        ->assertJsonPath('data.sunnah.0.id', $sunnah->id);
});

test('public list suggestions endpoint sorts by sort order then used count', function () {
    DuaList::factory()->create([
        'slug' => 'sorted-suggestions-list',
        'occasion' => 'hajj',
    ]);

    $secondSortHigherUsage = DuaSuggestion::factory()->global()->create([
        'title' => 'Second sort, higher usage',
        'source_type' => 'general',
        'sort_order' => 2,
        'used_count' => 50,
    ]);

    $secondSortLowerUsage = DuaSuggestion::factory()->global()->create([
        'title' => 'Second sort, lower usage',
        'source_type' => 'general',
        'sort_order' => 2,
        'used_count' => 10,
    ]);

    $firstSort = DuaSuggestion::factory()->global()->create([
        'title' => 'First sort',
        'source_type' => 'general',
        'sort_order' => 1,
        'used_count' => 0,
    ]);

    $response = $this->getJson('/api/v1/public/lists/sorted-suggestions-list/suggestions')
        ->assertOk();

    $ids = collect($response->json('data.general'))->pluck('id')->all();

    expect($ids)->toBe([
        $firstSort->id,
        $secondSortHigherUsage->id,
        $secondSortLowerUsage->id,
    ]);
});
