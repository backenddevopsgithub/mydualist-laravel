<?php

namespace App\Domains\Notifications\Jobs;

use App\Domains\Notifications\Services\DailyDigestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendDailyDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(DailyDigestService $digest): void
    {
        $digest->sendPendingDigests();
    }
}
