<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('arafah performance indexes exist on dua_submissions and dua_lists', function () {
    $submissionIndexNames = collect(Schema::getIndexes('dua_submissions'))
        ->pluck('name')
        ->all();

    $listIndexNames = collect(Schema::getIndexes('dua_lists'))
        ->pluck('name')
        ->all();

    expect($submissionIndexNames)->toContain(
        'dua_submissions_reported_at_index',
        'dua_submissions_list_personal_id_index',
        'dua_submissions_list_personal_lock_visibility_index',
        'dua_submissions_list_digest_pending_index',
    )->and($listIndexNames)->toContain(
        'dua_lists_email_frequency_status_index',
        'dua_lists_status_published_at_index',
    );
});

test('arafah performance indexes migration is reversible', function () {
    $this->artisan('migrate:rollback', [
        '--step' => 1,
    ])->assertSuccessful();

    $submissionIndexNames = collect(Schema::getIndexes('dua_submissions'))
        ->pluck('name')
        ->all();

    expect($submissionIndexNames)->not->toContain(
        'dua_submissions_reported_at_index',
        'dua_submissions_list_personal_id_index',
        'dua_submissions_list_personal_lock_visibility_index',
        'dua_submissions_list_digest_pending_index',
    );

    $this->artisan('migrate')->assertSuccessful();
});
