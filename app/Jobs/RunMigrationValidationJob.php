<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class RunMigrationValidationJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function handle(): void
    {
        Artisan::call('migrate:validate');
    }
}
