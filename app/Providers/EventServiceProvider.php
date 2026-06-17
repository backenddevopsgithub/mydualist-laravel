<?php

namespace App\Providers;

use App\Events\CommunityDuaCompletedByPilgrim;
use App\Events\DuaListCreated;
use App\Events\DuaSubmissionCompleted;
use App\Events\DuaSubmissionsCreated;
use App\Events\UserEmailVerified;
use App\Listeners\LogEmailNotificationDelivery;
use App\Listeners\SendCommunityDuaCompletedEmail;
use App\Listeners\SendDuaCompletedEmail;
use App\Listeners\SendDuaCompletedWhatsApp;
use App\Listeners\SendListCreatedEmail;
use App\Listeners\SendSubmissionTransactionalEmails;
use App\Listeners\SendWelcomeAndPendingListEmails;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [
        UserEmailVerified::class => [
            SendWelcomeAndPendingListEmails::class,
        ],
        DuaListCreated::class => [
            SendListCreatedEmail::class,
        ],
        DuaSubmissionsCreated::class => [
            SendSubmissionTransactionalEmails::class,
        ],
        DuaSubmissionCompleted::class => [
            SendDuaCompletedEmail::class,
            SendDuaCompletedWhatsApp::class,
        ],
        CommunityDuaCompletedByPilgrim::class => [
            SendCommunityDuaCompletedEmail::class,
        ],
        NotificationSent::class => [
            [LogEmailNotificationDelivery::class, 'handleSent'],
        ],
        NotificationFailed::class => [
            [LogEmailNotificationDelivery::class, 'handleFailed'],
        ],
    ];
}
