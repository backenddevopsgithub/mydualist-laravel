<?php

namespace App\Domains\Auth\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;
}
