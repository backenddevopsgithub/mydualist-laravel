<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Services\LegacyImport\Submissions\Import\SubmissionImportSource;
use App\Services\LegacyImport\Submissions\LegacySubmissionArrayMigrator;
use App\Services\LegacyImport\Submissions\SubmissionImportService;
use App\Services\LegacyImport\Support\LegacyWhatsAppPhoneParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

function importLegacyListsForSubmissionSchemaTests(): void
{
    Artisan::call('migrate:users', ['--csv' => base_path('tests/Fixtures/legacy-import/users.csv')]);
    Artisan::call('migrate:lists', ['--csv' => base_path('tests/Fixtures/legacy-import/lists.csv')]);
}

test('dua submissions schema supports negative legacy placeholder wp_post_id values', function () {
    $list = DuaList::factory()->create();

    $submission = DuaSubmission::query()->create([
        'wp_post_id' => -7370006,
        'dua_list_id' => $list->id,
        'first_name' => 'Legacy',
        'last_name' => 'Guest',
        'email' => null,
        'content' => 'Placeholder submission from legacy array import.',
        'status' => 'pending',
    ]);

    expect($submission->refresh()->wp_post_id)->toBe(-7370006);
});

test('dua submissions schema supports whatsapp_phone values up to 30 characters', function () {
    $list = DuaList::factory()->create();
    $longPhone = str_repeat('9', 30);

    $submission = DuaSubmission::query()->create([
        'dua_list_id' => $list->id,
        'first_name' => 'Long',
        'last_name' => 'Phone',
        'email' => 'long-phone@example.com',
        'content' => 'Testing long phone storage.',
        'status' => 'pending',
        'whatsapp_phone' => $longPhone,
    ]);

    expect($submission->refresh()->whatsapp_phone)->toBe($longPhone)
        ->and(strlen((string) $submission->whatsapp_phone))->toBe(30);
});

test('legacy whatsapp phone parser coerces numeric values to strings', function () {
    $parsed = LegacyWhatsAppPhoneParser::parse(447700900123);

    expect($parsed['whatsapp_country_code'])->toBe('+44')
        ->and($parsed['whatsapp_phone'])->toBe('7700900123')
        ->and($parsed['is_valid'])->toBeTrue();
});

test('legacy whatsapp phone parser truncates malformed numbers to column limit', function () {
    $malformed = '2312312312313123123123123123123123123';
    $parsed = LegacyWhatsAppPhoneParser::parse($malformed);

    expect(strlen((string) $parsed['whatsapp_phone']))->toBeLessThanOrEqual(LegacyWhatsAppPhoneParser::WHATSAPP_PHONE_MAX_LENGTH)
        ->and($parsed['whatsapp_phone'])->toBe(substr('2312312312313123123123123123123123123', 3, 30));
});

test('submission import service persists guest legacy array submissions with negative wp_post_id', function () {
    importLegacyListsForSubmissionSchemaTests();

    $records = app(LegacySubmissionArrayMigrator::class)->expand(
        301,
        serialize([
            [
                'first_name' => 'Guest',
                'last_name' => 'Visitor',
                'email' => null,
                'gender' => 'female',
                'message' => 'Please make dua for my family.',
                'phone' => 2312312312313123123123,
                'show' => 1,
                'status' => 0,
            ],
        ]),
        false,
    );

    $source = new class($records) implements SubmissionImportSource
    {
        public function __construct(private Collection $records) {}

        public function records(): iterable
        {
            yield from $this->records;
        }
    };

    app(SubmissionImportService::class)->import($source, dryRun: false);

    $expectedWpPostId = -3010001;
    $submission = DuaSubmission::query()->where('wp_post_id', $expectedWpPostId)->first();

    expect($submission)->not->toBeNull()
        ->and($submission->email)->toBeNull()
        ->and($submission->first_name)->toBe('Guest')
        ->and(strlen((string) $submission->whatsapp_phone))->toBeLessThanOrEqual(30)
        ->and(is_string($submission->whatsapp_phone))->toBeTrue();
});

test('migrate submissions imports guest csv submissions without email', function () {
    importLegacyListsForSubmissionSchemaTests();

    $csvPath = storage_path('app/testing-submission-guest.csv');
    file_put_contents($csvPath, implode("\n", [
        'wp_post_id,list_wp_post_id,first_name,last_name,email,gender,content,type,show,status,phone,created_at',
        '-7370008,301,Guest,,,female,Anonymous dua request.,,1,0,,2024-01-26 09:00:00',
    ]));

    Artisan::call('migrate:submissions', ['--csv' => $csvPath]);

    $submission = DuaSubmission::query()->where('wp_post_id', -7370008)->first();

    expect($submission)->not->toBeNull()
        ->and($submission->email)->toBeNull()
        ->and($submission->first_name)->toBe('Guest');
});

test('migrate submissions imports long malformed phone values from csv', function () {
    importLegacyListsForSubmissionSchemaTests();

    $csvPath = storage_path('app/testing-submission-long-phone.csv');
    file_put_contents($csvPath, implode("\n", [
        'wp_post_id,list_wp_post_id,first_name,last_name,email,gender,content,type,show,status,phone,created_at',
        '-7370007,301,Long,Number,long@example.com,female,Please make dua.,,1,0,2312312312313123123123123123123123123,2024-01-25 09:00:00',
    ]));

    Artisan::call('migrate:submissions', ['--csv' => $csvPath]);

    $submission = DuaSubmission::query()->where('wp_post_id', -7370007)->first();

    expect($submission)->not->toBeNull()
        ->and(strlen((string) $submission->whatsapp_phone))->toBeLessThanOrEqual(30)
        ->and(is_string($submission->whatsapp_phone))->toBeTrue();
});

test('legacy submission array migrator generates negative placeholder wp_post_id values', function () {
    $records = app(LegacySubmissionArrayMigrator::class)->expand(
        737,
        serialize([
            ['first_name' => 'Guest', 'message' => 'Dua request', 'phone' => '1234567890'],
        ]),
        false,
    );

    expect($records)->toHaveCount(1)
        ->and($records->first()->wpPostId)->toBe(-7370001);
});

test('submission schema migration preserves wp_post_id uniqueness', function () {
    $indexes = collect(Schema::getIndexes('dua_submissions'))
        ->filter(fn (array $index): bool => in_array('wp_post_id', $index['columns'], true))
        ->values();

    expect($indexes)->not->toBeEmpty()
        ->and($indexes->first()['unique'] ?? false)->toBeTrue();
});
