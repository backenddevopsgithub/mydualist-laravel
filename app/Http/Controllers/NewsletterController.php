<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Jobs\SyncMailchimpMemberToTagJob;
use App\Models\NewsletterSubscription;
use App\Support\MailchimpConfiguration;
use App\Support\MailchimpTag;
use Illuminate\Http\RedirectResponse;

class NewsletterController extends Controller
{
    public function store(StoreNewsletterSubscriptionRequest $request): RedirectResponse
    {
        $subscription = NewsletterSubscription::query()->firstOrCreate(
            ['email' => $request->validated('email')],
            ['source' => 'homepage'],
        );

        $message = $subscription->wasRecentlyCreated
            ? 'Thanks! You are subscribed to our newsletter.'
            : 'You are already subscribed to our newsletter.';

        if (MailchimpConfiguration::isEnabled()) {
            SyncMailchimpMemberToTagJob::dispatch([
                'email' => $request->validated('email'),
                'first_name' => '',
                'last_name' => '',
            ], MailchimpTag::FooterNewsletter);
        }

        return back()->with('newsletter_status', $message);
    }
}
