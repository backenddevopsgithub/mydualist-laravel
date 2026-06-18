<?php

use App\Filament\Widgets\CategoryTrendsChart;
use App\Filament\Widgets\EmailHealthWidget;
use App\Filament\Widgets\PlatformStatsOverview;
use App\Filament\Widgets\SubmissionGrowthChart;
use App\Models\DuaList;
use App\Models\DuaSubmission;
use App\Models\User;
use App\Services\AdminDashboardCacheService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

test('admin dashboard widgets use configured cache ttl', function () {
    config(['mydualist.admin_dashboard.cache_ttl_seconds' => 900]);

    expect(app(AdminDashboardCacheService::class)->ttl())->toBe(900);
});

test('platform stats overview avoids aggregate queries on cached reload', function () {
    Cache::flush();

    $admin = User::factory()->admin()->create();
    DuaSubmission::factory()->count(3)->create();
    DuaList::factory()->count(2)->create();

    Livewire::actingAs($admin)->test(PlatformStatsOverview::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($admin)->test(PlatformStatsOverview::class);

    $aggregateQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_submissions`')
            || str_contains(strtolower($query), 'from `users`'));

    expect($aggregateQueries)->toBeEmpty();
});

test('submission growth chart avoids dua_submissions queries on cached reload', function () {
    Cache::flush();

    $admin = User::factory()->admin()->create();
    DuaSubmission::factory()->count(2)->create();

    Livewire::actingAs($admin)->test(SubmissionGrowthChart::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($admin)->test(SubmissionGrowthChart::class);

    $submissionQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_submissions`'));

    expect($submissionQueries)->toBeEmpty();
});

test('category trends chart avoids dua_lists aggregate queries on cached reload', function () {
    Cache::flush();

    $admin = User::factory()->admin()->create();
    DuaList::factory()->count(2)->create(['occasion' => 'wedding']);

    Livewire::actingAs($admin)->test(CategoryTrendsChart::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($admin)->test(CategoryTrendsChart::class);

    $listAggregateQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `dua_lists`')
            && str_contains(strtolower($query), 'group by'));

    expect($listAggregateQueries)->toBeEmpty();
});

test('email health widget avoids metrics queries on cached reload', function () {
    Cache::flush();

    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)->test(EmailHealthWidget::class);

    DB::flushQueryLog();
    DB::enableQueryLog();

    Livewire::actingAs($admin)->test(EmailHealthWidget::class);

    $emailLogQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains(strtolower($query), 'from `email_send_logs`'));

    expect($emailLogQueries)->toBeEmpty();
});
