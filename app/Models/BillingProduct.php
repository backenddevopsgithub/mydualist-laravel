<?php

namespace App\Models;

use App\Enums\BillingProductScope;
use Database\Factories\BillingProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingProduct extends Model
{
    /** @use HasFactory<BillingProductFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'external_product_id',
        'name',
        'scope',
        'stackable',
        'requires_authentication',
        'amount_minor',
        'currency',
        'active',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => BillingProductScope::class,
            'stackable' => 'boolean',
            'requires_authentication' => 'boolean',
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * @return HasMany<BillingPurchase, $this>
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(BillingPurchase::class);
    }

    public function requiresAuthentication(): bool
    {
        return (bool) $this->requires_authentication;
    }
}
