<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNewsletterSubscriptionRequest;
use App\Models\NewsletterSubscription;
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

        return back()->with('newsletter_status', $message);
    }
}
