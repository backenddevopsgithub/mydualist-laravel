<?php

use App\Models\DuaList;
use App\Models\User;
use App\Services\LegacyImport\Support\WordPressListOwnerResolver;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function writeListOwnerSql(string $filename, string $body): string
{
    $path = storage_path("app/{$filename}");
    file_put_contents($path, $body);

    return $path;
}

function listOwnerSqlFixture(string $postsSql, string $postmetaSql): string
{
    return <<<SQL
INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_registered`, `display_name`) VALUES
(42, 'creator@example.com', '\$P\$Bexamplehash', 'creator', 'creator@example.com', '2024-01-15 10:00:00', 'Sara Ali'),
(269, 'owner@example.com', '\$P\$Bownerhash', 'owner', 'owner@example.com', '2024-02-01 10:00:00', 'Current Owner');
INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(1, 42, 'first_name', 'Sara'),
(2, 42, 'dua_limit_per_person', '4'),
(3, 42, 'dua_display_order', 'order_by_person'),
(4, 42, 'frequency_of_emails', 'every_dua'),
(5, 269, 'first_name', 'Current'),
(6, 269, 'dua_limit_per_person', '3'),
(7, 269, 'dua_display_order', 'order_by_date'),
(8, 269, 'frequency_of_emails', 'daily');
{$postsSql}
{$postmetaSql}
SQL;
}

test('wordpress list owner resolver does not log when owner fields match', function () {
    Log::spy();

    $ownership = WordPressListOwnerResolver::resolve(301, 42, ['user' => '42']);

    expect($ownership['owner_wp_id'])->toBe(42);

    Log::shouldNotHaveReceived('warning');
});

test('wordpress list owner resolver prefers post_author over stale meta user', function () {
    Log::spy();

    $ownership = WordPressListOwnerResolver::resolve(383, 269, ['user' => '123']);

    expect($ownership['owner_wp_id'])->toBe(269)
        ->and($ownership['post_author'])->toBe(269)
        ->and($ownership['meta_user'])->toBe(123);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('WordPress list owner field mismatch', [
            'wp_post_id' => 383,
            'post_author' => 269,
            'meta_user' => 123,
        ]);
});

test('wordpress list owner resolver falls back to meta user when post_author is missing', function () {
    Log::spy();

    $ownership = WordPressListOwnerResolver::resolve(500, 0, ['user' => '42']);

    expect($ownership['owner_wp_id'])->toBe(42);

    Log::shouldHaveReceived('warning')->once();
});

test('wordpress list owner resolver returns null when both owner fields are invalid', function () {
    Log::spy();

    $ownership = WordPressListOwnerResolver::resolve(600, 0, []);

    expect($ownership['owner_wp_id'])->toBeNull();

    Log::shouldNotHaveReceived('warning');
});

test('migrate lists imports post 383 under post_author when meta user is deleted', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(383, 269, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Reassigned List', '', 'publish', 'closed', 'closed', '', 'reassigned-list-383', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=383', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(20, 383, 'user', '123'),
(21, 383, 'category', 'Umrah'),
(22, 383, 'tripStart', '2024-05-01'),
(23, 383, 'tripEnd', '2024-05-10'),
(24, 383, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-mismatch.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);

    $exitCode = Artisan::call('migrate:lists', [
        '--sql' => $path,
        '--report' => storage_path('app/testing-list-owner-mismatch-report.json'),
    ]);

    $list = DuaList::query()->where('wp_post_id', 383)->first();
    $owner = User::query()->where('wp_legacy_id', 269)->first();
    $report = json_decode(file_get_contents(storage_path('app/testing-list-owner-mismatch-report.json')), true);

    expect($exitCode)->toBe(0)
        ->and($report['counts']['failed'])->toBe(0)
        ->and($list)->not->toBeNull()
        ->and($owner)->not->toBeNull()
        ->and($list->user_id)->toBe($owner->id)
        ->and($list->dua_limit_per_person)->toBe(3);
});

test('migrate lists dry run succeeds when stale meta user is missing but post_author resolves', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(383, 269, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Reassigned List', '', 'publish', 'closed', 'closed', '', 'reassigned-list-383', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=383', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(20, 383, 'user', '123'),
(21, 383, 'category', 'Umrah'),
(22, 383, 'tripStart', '2024-05-01'),
(23, 383, 'tripEnd', '2024-05-10'),
(24, 383, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-dry-run.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);

    $exitCode = Artisan::call('migrate:lists', [
        '--sql' => $path,
        '--dry-run' => true,
        '--report' => storage_path('app/testing-list-owner-dry-run-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-list-owner-dry-run-report.json')), true);

    expect($exitCode)->toBe(0)
        ->and($report['counts']['failed'])->toBe(0)
        ->and($report['counts']['imported'])->toBe(1)
        ->and(DuaList::query()->where('wp_post_id', 383)->exists())->toBeFalse();
});

test('migrate lists imports list when post_author and meta user match', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(301, 42, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Matching Owner List', '', 'publish', 'closed', 'closed', '', 'matching-owner-301', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=301', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(10, 301, 'user', '42'),
(11, 301, 'category', 'Hajj'),
(12, 301, 'tripStart', '2024-05-01'),
(13, 301, 'tripEnd', '2024-05-10'),
(14, 301, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-matching.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);

    $list = DuaList::query()->where('wp_post_id', 301)->first();
    $owner = User::query()->where('wp_legacy_id', 42)->first();

    expect($list)->not->toBeNull()
        ->and($owner)->not->toBeNull()
        ->and($list->user_id)->toBe($owner->id);
});

test('migrate lists imports list using meta user when post_author is missing', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(302, 0, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Meta Fallback List', '', 'publish', 'closed', 'closed', '', 'meta-fallback-302', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=302', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(15, 302, 'user', '42'),
(16, 302, 'category', 'Ramadan'),
(17, 302, 'tripStart', '2024-05-01'),
(18, 302, 'tripEnd', '2024-05-10'),
(19, 302, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-meta-fallback.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);

    $list = DuaList::query()->where('wp_post_id', 302)->first();
    $owner = User::query()->where('wp_legacy_id', 42)->first();

    expect($list)->not->toBeNull()
        ->and($owner)->not->toBeNull()
        ->and($list->user_id)->toBe($owner->id);
});

test('migrate lists owner resolution is idempotent on repeated runs', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(383, 269, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Reassigned List', '', 'publish', 'closed', 'closed', '', 'reassigned-list-383', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=383', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(20, 383, 'user', '123'),
(21, 383, 'category', 'Umrah'),
(22, 383, 'tripStart', '2024-05-01'),
(23, 383, 'tripEnd', '2024-05-10'),
(24, 383, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-idempotent.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);
    Artisan::call('migrate:lists', ['--sql' => $path]);
    Artisan::call('migrate:lists', [
        '--sql' => $path,
        '--report' => storage_path('app/testing-list-owner-idempotent-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-list-owner-idempotent-report.json')), true);

    expect(DuaList::query()->where('wp_post_id', 383)->count())->toBe(1)
        ->and(DuaList::query()->where('wp_post_id', 383)->value('user_id'))
        ->toBe(User::query()->where('wp_legacy_id', 269)->value('id'))
        ->and($report['counts']['imported'])->toBe(0)
        ->and($report['counts']['updated'])->toBe(1)
        ->and($report['counts']['failed'])->toBe(0);
});

test('migrate lists skips orphaned list when both owner fields are invalid', function () {
    Http::fake();

    $sql = listOwnerSqlFixture(
        <<<'POSTS'
INSERT INTO `wp_posts` (`ID`, `post_author`, `post_date`, `post_date_gmt`, `post_content`, `post_title`, `post_excerpt`, `post_status`, `comment_status`, `ping_status`, `post_password`, `post_name`, `to_ping`, `pinged`, `post_modified`, `post_modified_gmt`, `post_content_filtered`, `post_parent`, `guid`, `menu_order`, `post_type`, `post_mime_type`, `comment_count`) VALUES
(900, 0, '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 'Orphaned List', '', 'publish', 'closed', 'closed', '', 'orphaned-list-900', '', '', '2024-01-20 09:00:00', '2024-01-20 09:00:00', '', 0, 'https://example.test/?post_type=dua_list&p=900', 0, 'dua_list', '', 0);
POSTS,
        <<<'META'
INSERT INTO `wp_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(30, 900, 'category', 'Umrah'),
(31, 900, 'tripStart', '2024-05-01'),
(32, 900, 'tripEnd', '2024-05-10'),
(33, 900, 'status', '1');
META,
    );

    $path = writeListOwnerSql('testing-list-owner-orphaned.sql', $sql);

    Artisan::call('migrate:users', ['--sql' => $path]);

    $exitCode = Artisan::call('migrate:lists', [
        '--sql' => $path,
        '--report' => storage_path('app/testing-list-owner-orphaned-report.json'),
    ]);

    $report = json_decode(file_get_contents(storage_path('app/testing-list-owner-orphaned-report.json')), true);

    expect($exitCode)->toBe(0)
        ->and(DuaList::query()->where('wp_post_id', 900)->exists())->toBeFalse()
        ->and($report['counts']['failed'])->toBe(0)
        ->and($report['counts']['imported'])->toBe(0);
});
