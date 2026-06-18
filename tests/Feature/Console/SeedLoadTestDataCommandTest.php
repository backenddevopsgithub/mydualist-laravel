<?php

use App\Console\Commands\SeedLoadTestDataCommand;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

test('load test seed command creates manifest and deterministic fixtures', function () {
    $this->artisan('load-test:seed', ['--submissions' => 10, '--fresh' => true])
        ->assertSuccessful();

    $manifestPath = base_path('load-tests/fixtures/manifest.json');

    expect(File::exists($manifestPath))->toBeTrue();

    $manifest = json_decode(File::get($manifestPath), true);

    expect($manifest['owner']['email'])->toBe(SeedLoadTestDataCommand::USER_EMAIL)
        ->and($manifest['list']['slug'])->toBe(SeedLoadTestDataCommand::LIST_SLUG)
        ->and($manifest['list']['submission_count'])->toBe(10);

    $user = User::query()->where('email', SeedLoadTestDataCommand::USER_EMAIL)->first();
    $list = DuaList::query()->where('slug', SeedLoadTestDataCommand::LIST_SLUG)->first();

    expect($user)->not->toBeNull()
        ->and($list?->user_id)->toBe($user?->id)
        ->and(DuaSubmission::query()->where('dua_list_id', $list?->id)->count())->toBe(10);
});
