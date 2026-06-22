<?php

use App\Enums\UserRole;
use App\Models\DuaList;
use App\Models\DuaSuggestion;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('migrate users imports wp users with legacy password hash and role mapping', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/users.csv');

    Artisan::call('migrate:users', ['--csv' => $csvPath]);

    $admin = User::query()->where('wp_legacy_id', 42)->first();
    $subscriber = User::query()->where('wp_legacy_id', 43)->first();

    expect($admin)->not->toBeNull()
        ->and($admin->email)->toBe('creator@example.com')
        ->and($admin->first_name)->toBe('Sara')
        ->and($admin->gender)->toBe('female')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->wp_password_hash)->toBe('$P$Bexamplehash')
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->created_at?->toDateTimeString())->toBe('2024-01-15 10:00:00')
        ->and($subscriber)->not->toBeNull()
        ->and($subscriber->role)->toBe(UserRole::User)
        ->and($subscriber->email_verified_at)->toBeNull()
        ->and($subscriber->created_at?->toDateTimeString())->toBe('2024-02-01 12:00:00');
});

test('migrate users is idempotent on repeated runs', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/users.csv');
    $reportPath = storage_path('app/testing-legacy-users-report.json');

    Artisan::call('migrate:users', ['--csv' => $csvPath]);
    Artisan::call('migrate:users', [
        '--csv' => $csvPath,
        '--report' => $reportPath,
    ]);

    expect(User::query()->where('wp_legacy_id', 42)->count())->toBe(1);

    $report = json_decode(file_get_contents($reportPath), true);

    expect($report['counts']['imported'])->toBe(0)
        ->and($report['counts']['updated'])->toBe(2);
});

test('migrate users dry run does not persist users', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/users.csv');

    Artisan::call('migrate:users', [
        '--csv' => $csvPath,
        '--dry-run' => true,
    ]);

    expect(User::query()->where('wp_legacy_id', 42)->exists())->toBeFalse();
});

test('migrate suggestions merges quransunnahduas and suggestedduas records', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/suggestions.csv');

    Artisan::call('migrate:suggestions', ['--csv' => $csvPath]);

    $quran = DuaSuggestion::query()->where('wp_post_id', 201)->first();
    $keyword = DuaSuggestion::query()->where('wp_post_id', 202)->first();

    expect($quran)->not->toBeNull()
        ->and($quran->source_type)->toBe('quran')
        ->and($quran->source_reference)->toBe('Quran 2:201')
        ->and($quran->used_count)->toBe(12)
        ->and($keyword)->not->toBeNull()
        ->and($keyword->source_type)->toBe('general')
        ->and($keyword->used_count)->toBe(3);
});

test('migrate suggestions is idempotent on repeated runs', function () {
    $csvPath = base_path('tests/Fixtures/legacy-import/suggestions.csv');

    Artisan::call('migrate:suggestions', ['--csv' => $csvPath]);
    Artisan::call('migrate:suggestions', ['--csv' => $csvPath]);

    expect(DuaSuggestion::query()->where('wp_post_id', 201)->count())->toBe(1);
});

test('migrate lists preserves slug owner preferences and cover image', function () {
    Http::fake([
        'https://thepilgrim.co/*' => Http::response('fake-cover-bytes', 200, ['Content-Type' => 'image/jpeg']),
    ]);

    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);
    Artisan::call('migrate:lists', ['--csv' => base_path('tests/Fixtures/legacy-import/lists.csv')]);

    $list = DuaList::query()->where('wp_post_id', 301)->first();
    $owner = User::query()->where('wp_legacy_id', 42)->first();

    expect($list)->not->toBeNull()
        ->and($list->slug)->toBe('sara-umrah-301')
        ->and($list->user_id)->toBe($owner->id)
        ->and($list->occasion)->toBe('umrah')
        ->and($list->dua_limit_per_person)->toBe(5)
        ->and($list->display_order)->toBe('gender')
        ->and($list->email_frequency)->toBe('daily_summary')
        ->and($list->published_at)->not->toBeNull()
        ->and($list->created_at?->toDateTimeString())->toBe('2024-01-20 09:00:00')
        ->and($list->cover_image_path)->toStartWith('list-covers/301.');

    Storage::disk('public')->assertExists($list->cover_image_path);
});

test('migrate lists dry run does not persist lists or download images', function () {
    Http::fake();

    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);
    Artisan::call('migrate:lists', [
        '--csv' => base_path('tests/Fixtures/legacy-import/lists.csv'),
        '--dry-run' => true,
    ]);

    expect(DuaList::query()->where('wp_post_id', 301)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('list-covers/301.jpg');
});

test('migrate lists fails when owner has not been imported', function () {
    Artisan::call('migrate:lists', ['--csv' => base_path('tests/Fixtures/legacy-import/lists.csv')]);

    expect(DuaList::query()->where('wp_post_id', 301)->exists())->toBeFalse();
});

test('sql list import source parses dua_list posts', function () {
    $sql = <<<'SQL'
INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `display_name`) VALUES
(42, 'creator@example.com', '$P$Bexamplehash', 'creator', 'creator@example.com', '2024-01-15 10:00:00', 'Sara Ali');
INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(1, 42, 'first_name', 'Sara'),
(2, 42, 'dua_limit_per_person', '4'),
(3, 42, 'dua_display_order', 'order_by_person'),
(4, 42, 'frequency_of_emails', 'every_dua');
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(301, 42, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'SQL Umrah List', '', 'publish', 'closed', 'closed', '', 'sql-umrah-list', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=301', 0, 'dua_list', '', 0);
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(10, 301, 'user', '42'),
(11, 301, 'category', 'Hajj'),
(12, 301, 'tripStart', '2024-05-01'),
(13, 301, 'tripEnd', '2024-05-10'),
(14, 301, 'status', '1');
SQL;

    $path = storage_path('app/testing-legacy-lists.sql');
    file_put_contents($path, $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);

    $list = DuaList::query()->where('wp_post_id', 301)->first();

    expect($list)->not->toBeNull()
        ->and($list->slug)->toBe('sql-umrah-list')
        ->and($list->occasion)->toBe('hajj')
        ->and($list->dua_limit_per_person)->toBe(4)
        ->and($list->display_order)->toBe('person')
        ->and($list->email_frequency)->toBe('every_submission')
        ->and($list->created_at?->toDateTimeString())->toBe('2024-01-20 09:00:00');
});
