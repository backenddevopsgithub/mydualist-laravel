<?php

namespace App\Models;

use App\Enums\EntitlementKey;
use App\Enums\EntitlementProductType;
use Database\Factories\EntitlementGrantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntitlementGrant extends Model
{
    /** @use HasFactory<EntitlementGrantFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'dua_list_id',
        'entitlement_key',
        'quantity',
        'is_stackable',
        'dedupe_key',
        'source_purchase_id',
        'granted_at',
        'expires_at',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'entitlement_key' => EntitlementKey::class,
            'is_stackable' => 'boolean',
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
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
     * @return BelongsTo<BillingPurchase, $this>
     */
    public function sourcePurchase(): BelongsTo
    {
        return $this->belongsTo(BillingPurchase::class, 'source_purchase_id');
    }

    public function isActive(): bool
    {
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function productLabel(): string
    {
        $product = EntitlementProductType::fromEntitlementKey($this->entitlement_key);

        return $product?->label() ?? str($this->entitlement_key->value)->headline()->toString();
    }

    public function grantedByLabel(): string
    {
        if ($email = data_get($this->metadata, 'granted_by_email')) {
            return (string) $email;
        }

        if ($adminId = data_get($this->metadata, 'granted_by')) {
            return User::query()->find($adminId)?->email ?? "Admin #{$adminId}";
        }

        if ($this->source_purchase_id !== null) {
            return 'Purchase #'.$this->source_purchase_id;
        }

        return '—';
    }

    public static function dedupeKeyForUserGrant(int $userId, EntitlementKey|string $key): string
    {
        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        return sprintf('user:%d:%s', $userId, $keyValue);
    }

    public static function dedupeKeyForListGrant(int $duaListId, EntitlementKey|string $key): string
    {
        $keyValue = $key instanceof EntitlementKey ? $key->value : $key;

        return sprintf('list:%d:%s', $duaListId, $keyValue);
    }

    public static function dedupeKeyForPurchase(int $purchaseId): string
    {
        return sprintf('purchase:%d', $purchaseId);
    }
}
