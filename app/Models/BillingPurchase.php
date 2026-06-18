<?php

namespace App\Models;

use App\Enums\BillingPurchaseStatus;
use Database\Factories\BillingPurchaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BillingPurchase extends Model
{
    /** @use HasFactory<BillingPurchaseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wp_order_id',
        'billing_product_id',
        'user_id',
        'dua_list_id',
        'community_dua_id',
        'status',
        'payment_intent_id',
        'amount_minor',
        'currency',
        'idempotency_key',
        'fulfilled_at',
        'refunded_at',
        'disputed_at',
        'failure_reason',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BillingPurchaseStatus::class,
            'metadata' => 'array',
            'fulfilled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'disputed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<BillingProduct, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(BillingProduct::class, 'billing_product_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DuaList, $this>
     */
    public function duaList(): BelongsTo
    {
        return $this->belongsTo(DuaList::class);
    }

    /**
     * @return BelongsTo<CommunityDua, $this>
     */
    public function communityDua(): BelongsTo
    {
        return $this->belongsTo(CommunityDua::class);
    }

    /**
     * @return HasMany<BillingPurchaseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(BillingPurchaseEvent::class);
    }

    /**
     * @return HasMany<EntitlementGrant, $this>
     */
    public function entitlementGrants(): HasMany
    {
        return $this->hasMany(EntitlementGrant::class, 'source_purchase_id');
    }

    /**
     * @return HasOne<StripePayment, $this>
     */
    public function stripePayment(): HasOne
    {
        return $this->hasOne(StripePayment::class, 'stripe_payment_intent_id', 'payment_intent_id');
    }

    public function provider(): string
    {
        if ($this->wp_order_id !== null) {
            return 'woocommerce';
        }

        if ($this->payment_intent_id !== null) {
            return 'stripe';
        }

        return (string) data_get($this->metadata, 'provider', 'manual');
    }

    public function fulfillmentStatus(): string
    {
        if ($this->isRefunded()) {
            return 'refunded';
        }

        if ($this->isFulfilled()) {
            return 'fulfilled';
        }

        if ($this->status === BillingPurchaseStatus::Succeeded) {
            return 'unfulfilled';
        }

        return 'pending';
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeProvider(Builder $query, string $provider): Builder
    {
        return match ($provider) {
            'stripe' => $query->whereNotNull('payment_intent_id'),
            'woocommerce' => $query->whereNotNull('wp_order_id'),
            'manual' => $query
                ->whereNull('payment_intent_id')
                ->whereNull('wp_order_id'),
            default => $query,
        };
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilled_at !== null;
    }

    public function isRefunded(): bool
    {
        return $this->refunded_at !== null;
    }

    public function isDisputed(): bool
    {
        return $this->disputed_at !== null;
    }

    public function isUnfulfilled(): bool
    {
        return $this->status === BillingPurchaseStatus::Succeeded
            && $this->fulfilled_at === null;
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [
            BillingPurchaseStatus::RequiresPaymentMethod,
            BillingPurchaseStatus::RequiresConfirmation,
            BillingPurchaseStatus::Processing,
        ], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === BillingPurchaseStatus::Succeeded;
    }
}
