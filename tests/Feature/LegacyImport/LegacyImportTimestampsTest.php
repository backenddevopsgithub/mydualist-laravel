<?php

use App\Models\User;
use App\Services\LegacyImport\Support\LegacyImportTimestamps;
use Illuminate\Support\Carbon;

test('legacy import timestamps persist created_at outside fillable', function () {
    $user = User::factory()->create([
        'created_at' => '2026-06-19 12:00:00',
        'updated_at' => '2026-06-19 12:00:00',
    ]);

    LegacyImportTimestamps::apply($user, Carbon::parse('2023-05-10 08:30:00'));

    expect($user->fresh()->created_at?->toDateTimeString())->toBe('2023-05-10 08:30:00')
        ->and($user->fresh()->updated_at?->toDateTimeString())->toBe('2023-05-10 08:30:00');
});

test('migrate users updates created_at when re-importing existing users', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/users.csv');

    User::factory()->create([
        'email' => 'creator@example.com',
        'wp_legacy_id' => 42,
        'created_at' => '2026-06-19 17:00:00',
        'updated_at' => '2026-06-19 17:00:00',
    ]);

    Artisan::call('migrate:users', ['--csv' => $csvPath]);

    expect(User::query()->where('wp_legacy_id', 42)->first()?->created_at?->toDateTimeString())
        ->toBe('2024-01-15 10:00:00');
});
