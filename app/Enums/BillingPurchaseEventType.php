<?php

namespace App\Enums;

enum BillingPurchaseEventType: string
{
    case PaymentIntentCreated = 'payment_intent.created';
    case PaymentIntentSucceeded = 'payment_intent.succeeded';
    case PaymentIntentFailed = 'payment_intent.payment_failed';
    case ChargeRefunded = 'charge.refunded';
    case ChargeDisputeCreated = 'charge.dispute.created';
    case FulfillmentStarted = 'fulfillment.started';
    case FulfillmentApplied = 'fulfillment.applied';
    case WebhookFailure = 'webhook.failure';
    case ReconcileAttempt = 'reconcile.attempt';
}
