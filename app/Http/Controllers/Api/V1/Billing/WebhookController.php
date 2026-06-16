<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Domains\Billing\Services\BillingPurchaseWebhookService;
use App\Domains\Billing\Services\StripeCheckoutService;
use App\Http\Controllers\Api\V1\ApiController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends ApiController
{
    public function store(
        Request $request,
        StripeCheckoutService $stripe,
        BillingPurchaseWebhookService $webhooks,
    ): Response {
        try {
            $event = $stripe->constructWebhookEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (SignatureVerificationException) {
            return response('', 400);
        } catch (RuntimeException) {
            return response('', 503);
        }

        $webhooks->handle($event);

        return response('', 204);
    }
}
