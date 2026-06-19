<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function writeLegacyUsersCsv(string $filename, string $body): string
{
    $path = storage_path("app/{$filename}");
    file_put_contents($path, $body);

    return $path;
}

test('migrate users reconciles existing user matched by email', function () {
    $knownPassword = 'existing-laravel-secret';
    $user = User::factory()->create([
        'email' => 'arsalan@thepilgrim.co',
        'password' => Hash::make($knownPassword),
        'wp_legacy_id' => null,
        'wp_password_hash' => null,
        'first_name' => null,
        'last_name' => null,
        'role' => UserRole::Admin,
    ]);

    $csvPath = writeLegacyUsersCsv('testing-reconcile-by-email.csv', <<<'CSV'
wp_legacy_id,user_email,user_pass,first_name,last_name,gender,_verified,user_registered,wp_capabilities
36,arsalan@thepilgrim.co,$P$Bwplegacyhash,Arsalan,Pilgrim,Male,1,2024-01-10 08:00:00,a:1:{s:10:"subscriber";b:1;}
CSV
    );

    $reportPath = storage_path('app/testing-reconcile-by-email-report.json');

    Artisan::call('migrate:users', [
        '--csv' => $csvPath,
        '--report' => $reportPath,
    ]);

    $user->refresh();

    expect($user->wp_legacy_id)->toBe(36)
        ->and($user->wp_password_hash)->toBe('$P$Bwplegacyhash')
        ->and($user->first_name)->toBe('Arsalan')
        ->and($user->last_name)->toBe('Pilgrim')
        ->and($user->role)->toBe(UserRole::Admin)
        ->and(Hash::check($knownPassword, $user->password))->toBeTrue();

    $report = json_decode(file_get_contents($reportPath), true);

    expect($report['counts']['reconciled'])->toBe(1)
        ->and($report['counts']['failed'])->toBe(0);
});

test('migrate users updates existing user matched by wp_legacy_id', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/users.csv');

    Artisan::call('migrate:users', ['--csv' => $csvPath]);

    $admin = User::query()->where('wp_legacy_id', 42)->firstOrFail();
    $admin->update(['first_name' => 'Existing']);

    Artisan::call('migrate:users', ['--csv' => $csvPath]);

    $admin->refresh();

    expect($admin->first_name)->toBe('Existing')
        ->and($admin->email)->toBe('creator@example.com')
        ->and(User::query()->where('wp_legacy_id', 42)->count())->toBe(1);
});

test('migrate users fails when email belongs to a different wp_legacy_id', function () {
    User::factory()->create([
        'email' => 'conflict@example.com',
        'wp_legacy_id' => 99,
    ]);

    $csvPath = writeLegacyUsersCsv('testing-email-legacy-conflict.csv', <<<'CSV'
wp_legacy_id,user_email,user_pass,first_name,last_name,gender,_verified,user_registered,wp_capabilities
36,conflict@example.com,$P$Bconflict,Conflict,User,Male,0,2024-01-10 08:00:00,a:1:{s:10:"subscriber";b:1;}
CSV
    );

    $exitCode = Artisan::call('migrate:users', ['--csv' => $csvPath]);

    expect($exitCode)->toBe(1)
        ->and(User::query()->where('wp_legacy_id', 36)->exists())->toBeFalse()
        ->and(User::query()->where('wp_legacy_id', 99)->value('email'))->toBe('conflict@example.com');
});

test('migrate users reconciliation is idempotent on repeated runs', function () {
    $knownPassword = 'zmiah-login-secret';
    $user = User::factory()->create([
        'email' => 'zmiah.316@gmail.com',
        'password' => Hash::make($knownPassword),
        'wp_legacy_id' => null,
    ]);

    $csvPath = writeLegacyUsersCsv('testing-reconcile-idempotent.csv', <<<'CSV'
wp_legacy_id,user_email,user_pass,first_name,last_name,gender,_verified,user_registered,wp_capabilities
556,zmiah.316@gmail.com,$P$Bzmiahhash,Zain,Miah,Male,1,2024-03-01 12:00:00,a:1:{s:10:"subscriber";b:1;}
CSV
    );

    Artisan::call('migrate:users', ['--csv' => $csvPath]);

    $firstPasswordHash = $user->fresh()->password;

    Artisan::call('migrate:users', [
        '--csv' => $csvPath,
        '--report' => storage_path('app/testing-reconcile-idempotent-report.json'),
    ]);

    $user->refresh();
    $report = json_decode(file_get_contents(storage_path('app/testing-reconcile-idempotent-report.json')), true);

    expect($user->wp_legacy_id)->toBe(556)
        ->and($user->password)->toBe($firstPasswordHash)
        ->and(Hash::check($knownPassword, $user->password))->toBeTrue()
        ->and(User::query()->where('email', 'zmiah.316@gmail.com')->count())->toBe(1)
        ->and($report['counts']['imported'])->toBe(0)
        ->and($report['counts']['failed'])->toBe(0)
        ->and($report['counts']['reconciled'])->toBe(0)
        ->and($report['counts']['updated'])->toBe(1);
});

test('migrate lists dry run finds owners reconciled by email import', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('fake-cover-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    User::factory()->create([
        'email' => 'creator@example.com',
        'wp_legacy_id' => null,
    ]);

    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);

    expect(User::query()->where('wp_legacy_id', 42)->exists())->toBeTrue();

    $exitCode = Artisan::call('migrate:lists', [
        '--csv' => base_path('tests/Fixtures/legacy-import/lists.csv'),
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0);
});
