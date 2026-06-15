<?php

use App\Filament\Widgets\SystemHealthWidget;
use App\Models\User;
use App\Support\SchedulerHealth;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

test('scheduler heartbeat command persists cache timestamp and logs', function () {
    Cache::forget(SchedulerHealth::CACHE_KEY);

    Artisan::call('scheduler:heartbeat');

    expect(Cache::get(SchedulerHealth::CACHE_KEY))->not->toBeNull();
});

test('scheduler health status follows heartbeat thresholds', function () {
    expect(SchedulerHealth::status(now()->subMinute()))->toBe(SchedulerHealth::STATUS_HEALTHY);
    expect(SchedulerHealth::status(now()->subMinutes(3)))->toBe(SchedulerHealth::STATUS_WARNING);
    expect(SchedulerHealth::status(now()->subMinutes(6)))->toBe(SchedulerHealth::STATUS_OFFLINE);
    expect(SchedulerHealth::status(null))->toBe(SchedulerHealth::STATUS_OFFLINE);
});

test('scheduled infrastructure tasks are registered', function () {
    $this->artisan('schedule:list')
        ->assertSuccessful()
        ->expectsOutputToContain('scheduler:heartbeat')
        ->expectsOutputToContain('send-daily-digest')
        ->expectsOutputToContain('send-no-activity-reminder')
        ->expectsOutputToContain('send-closing-soon-reminder')
        ->expectsOutputToContain('send-list-image-reminder');
});

test('system health widget renders on admin dashboard', function () {
    $admin = User::factory()->admin()->create();

    Cache::forever(SchedulerHealth::CACHE_KEY, now());

    Livewire::actingAs($admin)
        ->test(SystemHealthWidget::class)
        ->assertSee('Healthy')
        ->assertSee('Queue Connection')
        ->assertSee('Pending Jobs')
        ->assertSee('Failed Jobs');
});
