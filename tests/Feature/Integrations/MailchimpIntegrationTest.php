<?php

use App\Data\MailchimpMemberData;
use App\Domains\Lists\Actions\CreateDuaListAction;
use App\Domains\Submissions\Actions\CreateDuaSubmissionAction;
use App\Domains\Submissions\Actions\TransitionDuaSubmissionStatusAction;
use App\Enums\DuaSubmissionStatus;
use App\Jobs\SyncMailchimpMemberToListJob;
use App\Jobs\SyncMailchimpMemberToTagJob;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\MailchimpRestrictionStore;
use App\Services\MailchimpService;
use App\Support\MailchimpTag;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'services.mailchimp.enabled' => true,
        'services.mailchimp.api_key' => 'test-key-us22',
        'services.mailchimp.server_prefix' => 'us22',
        'services.mailchimp.audience_id' => 'list123',
    ]);
});

test('mailchimp jobs are not dispatched when feature flag is disabled', function () {
    config(['services.mailchimp.enabled' => false]);
    Queue::fake();

    $user = User::factory()->create(['email' => 'creator@example.com']);

    app(CreateDuaListAction::class)($user, [
        'title' => 'Ramadan List',
        'occasion' => 'ramadan',
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-31',
    ]);

    Queue::assertNotPushed(SyncMailchimpMemberToListJob::class);
});

test('list creation dispatches mailchimp list creator sync job', function () {
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'creator@example.com',
        'first_name' => 'Amina',
        'last_name' => 'Khan',
    ]);

    app(CreateDuaListAction::class)($user, [
        'title' => 'Hajj 2027',
        'occasion' => 'hajj',
        'start_date' => '2026-06-01',
        'end_date' => '2026-06-30',
    ]);

    Queue::assertPushed(SyncMailchimpMemberToListJob::class, function (SyncMailchimpMemberToListJob $job): bool {
        return $job->tag === MailchimpTag::ListCreator
            && $job->member['email'] === 'creator@example.com'
            && $job->member['first_name'] === 'Amina'
            && $job->member['last_name'] === 'Khan'
            && $job->member['category'] === 'hajj'
            && $job->member['list_name'] === 'Hajj 2027'
            && $job->member['start_date'] === '01/06/2026'
            && $job->member['end_date'] === '30/06/2026';
    });
});

test('dua submission dispatches mailchimp submitter sync job with wordpress submission count', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->for($owner)->create();

    DuaSubmission::factory()->for($duaList)->create([
        'email' => 'submitter@example.com',
        'is_personal_dua' => false,
    ]);

    app(CreateDuaSubmissionAction::class)($duaList, [
        'first_name' => 'Yusuf',
        'last_name' => 'Ali',
        'email' => 'submitter@example.com',
        'content' => 'Please make dua for my family.',
    ]);

    Queue::assertPushed(SyncMailchimpMemberToListJob::class, function (SyncMailchimpMemberToListJob $job): bool {
        return $job->tag === MailchimpTag::Submitter
            && $job->member['email'] === 'submitter@example.com'
            && $job->member['submission_count'] === 2;
    });
});

test('submission completion dispatches review tag sync for visible submissions only', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->for($owner)->create();
    $submission = DuaSubmission::factory()->for($duaList)->create([
        'email' => 'visible@example.com',
        'is_locked' => false,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    Queue::assertPushed(SyncMailchimpMemberToTagJob::class, function (SyncMailchimpMemberToTagJob $job): bool {
        return $job->tag === MailchimpTag::DuaSubmitterReview
            && $job->needsValidationOnEmail === true
            && $job->member['email'] === 'visible@example.com';
    });
});

test('submission completion skips mailchimp for locked submissions', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $duaList = DuaList::factory()->for($owner)->create();
    $submission = DuaSubmission::factory()->for($duaList)->create([
        'email' => 'locked@example.com',
        'is_locked' => true,
        'status' => DuaSubmissionStatus::Pending,
    ]);

    app(TransitionDuaSubmissionStatusAction::class)($submission, DuaSubmissionStatus::Completed);

    Queue::assertNotPushed(SyncMailchimpMemberToTagJob::class);
});

test('newsletter subscribe dispatches footer newsletter tag sync', function () {
    Queue::fake();

    $this->post(route('newsletter.subscribe'), [
        'email' => 'reader@example.com',
    ])->assertRedirect();

    Queue::assertPushed(SyncMailchimpMemberToTagJob::class, function (SyncMailchimpMemberToTagJob $job): bool {
        return $job->tag === MailchimpTag::FooterNewsletter
            && $job->member['email'] === 'reader@example.com';
    });
});

test('mailchimp list sync maps payload for new members', function () {
    $hash = md5('creator@example.com');

    Http::fake([
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}" => Http::response([], 404),
        'https://us22.api.mailchimp.com/3.0/lists/list123/members' => Http::response(['id' => $hash], 200),
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}/tags" => Http::response([], 204),
    ]);

    $service = app(MailchimpService::class);

    $result = $service->addMemberToList(
        new MailchimpMemberData(
            email: 'creator@example.com',
            firstName: 'Amina',
            lastName: 'Khan',
            category: 'hajj',
            listName: 'Hajj 2027',
            startDate: '01/06/2026',
            endDate: '30/06/2026',
            submissionCount: 1,
        ),
        MailchimpTag::ListCreator,
    );

    expect($result)->toBeTrue();

    Http::assertSent(function ($request): bool {
        if ($request->method() !== 'POST' || ! str_ends_with($request->url(), '/lists/list123/members')) {
            return false;
        }

        return $request['email_address'] === 'creator@example.com'
            && $request['status'] === 'subscribed'
            && $request['merge_fields']['FNAME'] === 'Amina'
            && $request['merge_fields']['MMERGE13'] === 1
            && $request['merge_fields']['MMERGE6'] === 'Hajj 2027';
    });

    Http::assertSent(function ($request): bool {
        return $request->method() === 'POST'
            && str_contains($request->url(), '/tags')
            && $request['tags'][0]['name'] === 'Dua List Creator';
    });
});

test('mailchimp tag sync updates existing members idempotently', function () {
    $hash = md5('submitter@example.com');

    Http::fake([
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}" => Http::response(['id' => $hash], 200),
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}/tags" => Http::response([], 204),
    ]);

    $service = app(MailchimpService::class);

    $result = $service->addMemberToTag(
        new MailchimpMemberData(
            email: 'submitter@example.com',
            firstName: 'Yusuf',
            lastName: 'Ali',
        ),
        MailchimpTag::DuaSubmitterReview,
        needsValidationOnEmail: true,
    );

    expect($result)->toBeTrue();

    Http::assertNotSent(fn ($request): bool => $request->method() === 'POST' && str_ends_with($request->url(), '/members'));
    Http::assertSent(fn ($request): bool => $request->method() === 'POST' && str_contains($request->url(), '/tags'));
});

test('mailchimp restriction store prevents duplicate review tag syncs', function () {
    $hash = md5('submitter@example.com');

    Http::fake([
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}" => Http::response(['id' => $hash], 200),
        "https://us22.api.mailchimp.com/3.0/lists/list123/members/{$hash}/tags" => Http::response([], 204),
    ]);

    app(MailchimpRestrictionStore::class)->remember('submitter@example.com');

    $service = app(MailchimpService::class);

    $result = $service->addMemberToTag(
        new MailchimpMemberData(email: 'submitter@example.com', firstName: 'Yusuf'),
        MailchimpTag::DuaSubmitterReview,
        needsValidationOnEmail: true,
    );

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

test('mailchimp jobs retry three times with backoff', function () {
    $tagJob = new SyncMailchimpMemberToTagJob(
        ['email' => 'reader@example.com'],
        MailchimpTag::FooterNewsletter,
    );

    $listJob = new SyncMailchimpMemberToListJob(
        ['email' => 'creator@example.com'],
        MailchimpTag::ListCreator,
    );

    expect($tagJob->tries)->toBe(3)
        ->and($tagJob->backoff())->toBe([60, 300])
        ->and($listJob->tries)->toBe(3)
        ->and($listJob->backoff())->toBe([60, 300]);
});

test('disabled feature flag prevents queued mailchimp api calls', function () {
    config(['services.mailchimp.enabled' => false]);
    Http::fake();

    $job = new SyncMailchimpMemberToTagJob(
        ['email' => 'reader@example.com', 'first_name' => '', 'last_name' => ''],
        MailchimpTag::FooterNewsletter,
    );

    $job->handle(app(MailchimpService::class));

    Http::assertNothingSent();
});
