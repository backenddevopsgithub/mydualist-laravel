<?php

use App\Enums\DuaSubmissionStatus;
use App\Enums\UserStatus;
use App\Models\CmsPage;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\DuaSuggestion;
use App\Models\SeoMetadata;
use App\Models\User;
use App\Models\UserEntitlement;

test('admin operations routes are restricted to active admins', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->get('/admin/users')->assertRedirect('/admin/login');

    $this->actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk();
});

test('admin operations resources render core management pages', function () {
    $admin = User::factory()->admin()->create();

    $routes = [
        '/admin',
        '/admin/users',
        '/admin/dua-lists',
        '/admin/dua-submissions',
        '/admin/reported-duas',
        '/admin/cms-pages',
        '/admin/seo-metadatas',
        '/admin/dua-suggestions',
        '/admin/support-tickets',
        '/admin/stripe-payments',
        '/admin/billing-purchases',
        '/admin/entitlement-grants',
        '/admin/community-duas',
    ];

    foreach ($routes as $route) {
        $this->actingAs($admin)
            ->get($route)
            ->assertOk();
    }
});

test('admin cms seo and suggestions models persist editable operational content', function () {
    CmsPage::query()->create([
        'slug' => 'pricing',
        'title' => 'Pricing',
        'section' => 'pricing',
        'content' => 'Premium unlock copy.',
        'is_published' => true,
        'published_at' => now(),
        'meta_title' => 'MyDualist Pricing',
        'meta_description' => 'Upgrade your dua lists.',
        'canonical_url' => 'https://mydualist.test/pricing',
    ]);

    SeoMetadata::query()->create([
        'key' => 'home',
        'scope' => 'route',
        'route_name' => 'home',
        'meta_title' => 'MyDualist',
        'meta_description' => 'Create and collect duas.',
        'twitter_card' => 'summary_large_image',
    ]);

    DuaSuggestion::query()->create([
        'title' => 'Forgiveness dua',
        'category' => 'forgiveness',
        'content' => 'May Allah forgive us and have mercy on us.',
        'source_type' => 'general',
        'is_visible' => true,
        'sort_order' => 10,
    ]);

    $this->assertDatabaseHas('cms_pages', ['slug' => 'pricing', 'is_published' => true]);
    $this->assertDatabaseHas('seo_metadata', ['key' => 'home', 'scope' => 'route']);
    $this->assertDatabaseHas('dua_suggestions', ['title' => 'Forgiveness dua', 'is_visible' => true]);
});

test('admin moderation and premium state changes use existing domain statuses', function () {
    $user = User::factory()->create();
    $duaList = DuaList::factory()->create(['user_id' => $user->id]);
    $submission = DuaSubmission::factory()->create([
        'dua_list_id' => $duaList->id,
        'status' => DuaSubmissionStatus::Reported,
        'reported_at' => now(),
        'report_reason' => 'spam',
    ]);

    $user->forceFill(['status' => UserStatus::Suspended])->save();
    $duaList->forceFill(['status' => DuaList::STATUS_ARCHIVED])->save();
    $submission->forceFill([
        'status' => DuaSubmissionStatus::Hidden,
        'hidden_at' => now(),
    ])->save();

    $user->entitlements()->updateOrCreate(
        ['key' => UserEntitlement::KEY_PREMIUM, 'reference' => 'admin-'.$user->id],
        ['active' => true, 'source' => 'admin', 'unlocked_at' => now(), 'expires_at' => null],
    );

    expect($user->refresh()->status)->toBe(UserStatus::Suspended);
    expect($duaList->refresh()->status)->toBe(DuaList::STATUS_ARCHIVED);
    expect($submission->refresh()->status)->toBe(DuaSubmissionStatus::Hidden);
    $this->assertDatabaseHas('user_entitlements', [
        'user_id' => $user->id,
        'key' => UserEntitlement::KEY_PREMIUM,
        'source' => 'admin',
        'active' => true,
    ]);
});
