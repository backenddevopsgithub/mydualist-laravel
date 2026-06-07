<?php

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('support endpoint requires authentication', function () {
    $this->postJson('/api/v1/support', [
        'reason' => 'bug',
        'email' => 'user@example.com',
        'first_name' => 'Test',
        'surname' => 'User',
        'comments' => 'Something is broken on the page.',
    ])->assertUnauthorized();
});

test('authenticated user can submit support request', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
    ]);
    $this->actingAsUser($user);

    $this->postJson('/api/v1/support', [
        'reason' => 'bug',
        'email' => 'amina@example.com',
        'first_name' => 'Amina',
        'surname' => 'Khan',
        'comments' => 'The page is not loading correctly.',
        'image' => UploadedFile::fake()->image('bug.png', 800, 600),
    ])->assertCreated()
        ->assertJsonPath('message', 'Support request submitted successfully.')
        ->assertJsonPath('data.reason', 'bug')
        ->assertJsonPath('data.email', 'amina@example.com');

    $ticket = SupportTicket::query()->where('user_id', $user->id)->firstOrFail();

    expect($ticket->comments)->toBe('The page is not loading correctly.')
        ->and($ticket->image_path)->not->toBeNull();
});

test('support request validates reason and comments', function () {
    $this->actingAsUser();

    $this->postJson('/api/v1/support', [
        'reason' => 'invalid',
        'email' => 'user@example.com',
        'first_name' => 'Test',
        'surname' => 'User',
        'comments' => 'bad',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['reason', 'comments']);
});

test('unverified users cannot submit support requests', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->postJson('/api/v1/support', [
        'reason' => 'bug',
        'email' => 'user@example.com',
        'first_name' => 'Test',
        'surname' => 'User',
        'comments' => 'Something is broken on the page.',
    ])->assertForbidden();
});
