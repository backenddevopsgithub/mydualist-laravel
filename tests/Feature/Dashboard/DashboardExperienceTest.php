<?php

use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

test('authenticated user can view upgraded dashboard experience', function () {
    $user = User::factory()->create([
        'name' => 'Arsalan Test',
        'first_name' => 'Arsalan',
        'last_name' => 'Test',
    ]);

    DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Hajj 2027',
        'slug' => 'arsalan-hajj-1001',
        'occasion' => 'hajj',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('My Lists')
        ->assertSee('Active Lists')
        ->assertSee('Archived Lists')
        ->assertSee('Total Submissions')
        ->assertSee('Completed Duas')
        ->assertSee('Hajj 2027')
        ->assertSee('Copy link')
        ->assertSee('View submissions')
        ->assertSee('Overall progress')
        ->assertSee('Created on')
        ->assertSee(route('dashboard.lists.show', DuaList::query()->where('slug', 'arsalan-hajj-1001')->first()), false)
        ->assertSee('Dashboard')
        ->assertSee('Profile');
});

test('user can archive restore edit and delete owned lists', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Umrah 2027',
        'slug' => 'user-umrah-1002',
        'status' => DuaList::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->patch(route('dashboard.lists.archive', $duaList))
        ->assertRedirect(route('dashboard.archived'));

    expect($duaList->refresh()->status)->toBe(DuaList::STATUS_ARCHIVED);

    $this->actingAs($user)
        ->get(route('dashboard.archived'))
        ->assertOk()
        ->assertSee('Umrah 2027');

    $this->actingAs($user)
        ->patch(route('dashboard.lists.restore', $duaList))
        ->assertRedirect(route('dashboard'));

    expect($duaList->refresh()->status)->toBe(DuaList::STATUS_ACTIVE);

    $this->actingAs($user)
        ->patch(route('dashboard.lists.update', $duaList), [
            'title' => 'Updated Umrah',
            'occasion' => 'umrah',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ])
        ->assertRedirect(route('dashboard'));

    expect($duaList->refresh()->title)->toBe('Updated Umrah');

    $this->actingAs($user)
        ->delete(route('dashboard.lists.destroy', $duaList))
        ->assertRedirect(route('dashboard'));

    expect(DuaList::query()->whereKey($duaList->id)->exists())->toBeFalse();
});

test('users cannot manage lists they do not own', function () {
    $user = User::factory()->create();
    $otherList = DuaList::factory()->create();

    $this->actingAs($user)
        ->patch(route('dashboard.lists.archive', $otherList))
        ->assertForbidden();
});

test('profile can be updated password can change and user can logout', function () {
    $user = User::factory()->create([
        'password' => Hash::make('OldPassword123!'),
        'email' => 'old@example.com',
    ]);

    $this->actingAs($user)
        ->patch(route('dashboard.profile.update'), [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => 'new@example.com',
        ])
        ->assertRedirect(route('dashboard.profile'));

    expect($user->refresh()->name)->toBe('New Name')
        ->and($user->email)->toBe('new@example.com');

    $this->actingAs($user)
        ->patch(route('dashboard.profile.password'), [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertRedirect(route('dashboard.profile'));

    expect(Hash::check('NewPassword123!', $user->refresh()->password))->toBeTrue();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

test('profile list settings image upload and csv download work', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id, 'title' => 'Hajj List']);
    DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'content' => 'Please make dua for our family.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.profile'))
        ->assertOk()
        ->assertSee('List Settings')
        ->assertSee('Profile Settings')
        ->assertSee('Download Dua Submissions');

    $this->actingAs($user)
        ->patch(route('dashboard.profile.list-settings'), [
            'dua_list_id' => $duaList->id,
            'dua_limit_per_person' => 3,
            'display_order' => 'person',
            'email_frequency' => 'daily_summary',
        ])
        ->assertRedirect(route('dashboard.profile', ['tab' => 'list-settings']));

    expect($duaList->refresh()->dua_limit_per_person)->toBe(3)
        ->and($duaList->display_order)->toBe('person')
        ->and($duaList->email_frequency)->toBe('daily_summary');

    $this->actingAs($user)
        ->post(route('dashboard.profile.list-image'), [
            'dua_list_id' => $duaList->id,
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 800),
        ])
        ->assertRedirect(route('dashboard.profile', ['tab' => 'list-settings']));

    Storage::disk('public')->assertExists($duaList->refresh()->cover_image_path);

    $this->actingAs($user)
        ->post(route('dashboard.profile.submissions.export'), [
            'dua_list_id' => $duaList->id,
        ])
        ->assertRedirect(route('dashboard.profile'))
        ->assertSessionHas('status');
});

test('help and support page stores validated requests', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'first_name' => 'Amina',
        'last_name' => 'Khan',
        'email' => 'amina@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.support'))
        ->assertOk()
        ->assertSee('Reason for Contact')
        ->assertDontSee('Contact Us');

    $this->actingAs($user)
        ->post(route('dashboard.support.store'), [
            'reason' => 'bug',
            'email' => 'amina@example.com',
            'first_name' => 'Amina',
            'surname' => 'Khan',
            'comments' => 'The page is not loading correctly.',
            'image' => UploadedFile::fake()->image('bug.png', 800, 600),
        ])
        ->assertRedirect(route('dashboard.support'));

    expect(SupportTicket::query()->where('user_id', $user->id)->where('reason', 'bug')->exists())->toBeTrue();
});

test('authenticated users can access the homepage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('The easiest way to collect dua requests');
});

test('upgrade and my submissions foundations render', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => User::factory()->create()->id]);
    DuaSubmission::factory()->create([
        'user_id' => $user->id,
        'dua_list_id' => $duaList->id,
        'content' => 'Please remember my family in your duas.',
    ]);

    $this->actingAs($user)
        ->get(route('dashboard.upgrade'))
        ->assertOk()
        ->assertSee('Upgrade Plan')
        ->assertSee('Current Plan: Free')
        ->assertSee('25 More Dua Requests')
        ->assertSee('billing/purchases/start', false);

    $this->actingAs($user)
        ->get(route('dashboard.submissions'))
        ->assertOk()
        ->assertSee('My Submissions')
        ->assertSee('Please remember my family in your duas.');
});

test('public dua list renders from root slug and old list route redirects', function () {
    $user = User::factory()->create([
        'name' => 'Arsalan Test',
        'first_name' => 'Arsalan',
        'last_name' => 'Test',
    ]);

    $duaList = DuaList::factory()->create([
        'user_id' => $user->id,
        'title' => 'Hajj 2027',
        'slug' => 'arsalan-hajj-1001',
        'occasion' => 'hajj',
    ]);

    $this->get('/'.$duaList->slug)
        ->assertOk()
        ->assertSee('Hajj 2027')
        ->assertSee('Arsalan Test is collecting dua requests')
        ->assertSee('Submit a Dua Request')
        ->assertSee(route('dua-lists.submissions.store', $duaList), false)
        ->assertSee('+ Add Another Dua')
        ->assertSee('Copy Share Link');

    $this->get('/lists/'.$duaList->slug)
        ->assertRedirect(route('cms.show', $duaList));
});
