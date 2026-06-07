<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

test('profile update requires authentication', function () {
    $this->patchJson('/api/v1/profile', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'new@example.com',
    ])->assertUnauthorized();
});

test('authenticated user can update profile', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old@example.com',
    ]);
    $this->actingAsUser($user);

    $this->patchJson('/api/v1/profile', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'new@example.com',
    ])->assertOk()
        ->assertJsonPath('message', 'Profile updated successfully.')
        ->assertJsonPath('data.first_name', 'New')
        ->assertJsonPath('data.email', 'new@example.com');

    expect($user->refresh()->name)->toBe('New Name');
});

test('profile update validates unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $this->actingAsUser();

    $this->patchJson('/api/v1/profile', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'taken@example.com',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can change password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);
    $this->actingAsUser($user);

    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertOk()
        ->assertJsonPath('message', 'Password changed successfully.');

    expect(Hash::check('NewPassword123!', $user->refresh()->password))->toBeTrue();
});

test('password change rejects invalid current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
    ]);
    $this->actingAsUser($user);

    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'WrongPassword123!',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});

test('authenticated user can update list settings', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->patchJson('/api/v1/profile/list-settings', [
        'dua_list_id' => $list->id,
        'dua_limit_per_person' => 3,
        'display_order' => 'person',
        'email_frequency' => 'daily_summary',
    ])->assertOk()
        ->assertJsonPath('data.dua_limit_per_person', 3)
        ->assertJsonPath('data.display_order', 'person')
        ->assertJsonPath('data.email_frequency', 'daily_summary');
});

test('list settings rejects lists owned by another user', function () {
    $list = DuaList::factory()->create();
    $this->actingAsUser();

    $this->patchJson('/api/v1/profile/list-settings', [
        'dua_list_id' => $list->id,
        'display_order' => 'person',
        'email_frequency' => 'daily_summary',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['dua_list_id']);
});

test('authenticated user can upload list cover image', function () {
    Storage::fake('public');

    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);

    $this->postJson('/api/v1/profile/list-image', [
        'dua_list_id' => $list->id,
        'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
    ])->assertOk()
        ->assertJsonPath('message', 'List image updated successfully.');

    expect($list->refresh()->cover_image_path)->toStartWith('list-covers/');
    Storage::disk('public')->assertExists($list->cover_image_path);
});

test('authenticated user can export submissions csv', function () {
    $user = $this->actingAsUser();
    $list = DuaList::factory()->create(['user_id' => $user->id]);
    DuaSubmission::factory()->create([
        'dua_list_id' => $list->id,
        'content' => 'Please make dua for our family.',
    ]);

    $this->getJson('/api/v1/profile/submissions/export?dua_list_id='.$list->id)
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=utf-8');
});

test('unverified users cannot access profile mutation routes', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAsUser($user);

    $this->patchJson('/api/v1/profile', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'email' => 'new@example.com',
    ])->assertForbidden();
});
