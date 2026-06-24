<?php

namespace App\Providers;

use App\Events\CommunityDuaCompletedByPilgrim;
use App\Events\DuaListCreated;
use App\Events\DuaSubmissionCompleted;
use App\Events\DuaSubmissionsCreated;
use App\Events\UserEmailVerified;
use App\Listeners\LogEmailNotificationDelivery;
use App\Listeners\RecordImpersonationEnded;
use App\Listeners\RecordImpersonationStarted;
use App\Listeners\SendCommunityDuaCompletedEmail;
use App\Listeners\SendCommunityDuaCompletedWhatsApp;
use App\Listeners\SendDuaCompletedEmail;
use App\Listeners\SendDuaCompletedWhatsApp;
use App\Listeners\SendListCreatedEmail;
use App\Listeners\SendSubmissionTransactionalEmails;
use App\Listeners\SendWelcomeAndPendingListEmails;
use App\Listeners\SyncMailchimpOnListCreated;
use App\Listeners\SyncMailchimpOnSubmissionCompleted;
use App\Listeners\SyncMailchimpOnSubmissionsCreated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;

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
            SyncMailchimpOnListCreated::class,
        ],
        DuaSubmissionsCreated::class => [
            SendSubmissionTransactionalEmails::class,
            SyncMailchimpOnSubmissionsCreated::class,
        ],
        DuaSubmissionCompleted::class => [
            SendDuaCompletedEmail::class,
            SendDuaCompletedWhatsApp::class,
            SyncMailchimpOnSubmissionCompleted::class,
        ],
        CommunityDuaCompletedByPilgrim::class => [
            SendCommunityDuaCompletedEmail::class,
            SendCommunityDuaCompletedWhatsApp::class,
        ],
        NotificationSent::class => [
            [LogEmailNotificationDelivery::class, 'handleSent'],
        ],
        NotificationFailed::class => [
            [LogEmailNotificationDelivery::class, 'handleFailed'],
        ],
        TakeImpersonation::class => [
            RecordImpersonationStarted::class,
        ],
        LeaveImpersonation::class => [
            RecordImpersonationEnded::class,
        ],
    ];
}
