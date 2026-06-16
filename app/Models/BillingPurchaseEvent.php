<?php

namespace App\Models;

use App\Enums\BillingPurchaseEventType;
use Database\Factories\BillingPurchaseEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingPurchaseEvent extends Model
{
    /** @use HasFactory<BillingPurchaseEventFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'billing_purchase_id',
        'event_type',
        'stripe_event_id',
        'idempotency_key',
        'payload',
        'processed_at',
        'failed_at',
        'error_message',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_type' => BillingPurchaseEventType::class,
            'payload' => 'array',
            'processed_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BillingPurchase, $this>
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(BillingPurchase::class, 'billing_purchase_id');
    }

    public function isWebhookFailure(): bool
    {
        return $this->event_type === BillingPurchaseEventType::WebhookFailure
            || $this->failed_at !== null;
    }
}
