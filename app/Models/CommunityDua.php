<?php

namespace App\Models;

use App\Enums\CommunityDuaStatus;
use App\Enums\CommunityDuaType;
use Database\Factories\CommunityDuaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityDua extends Model
{
    /** @use HasFactory<CommunityDuaFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wp_post_id',
        'first_name',
        'last_name',
        'email',
        'gender',
        'whatsapp_country_code',
        'whatsapp_phone',
        'whatsapp_verified_at',
        'content',
        'type',
        'status',
        'required_completions',
        'completion_count',
        'is_visible',
        'stripe_payment_id',
        'fulfilled_at',
        'reported_at',
        'report_reason',
        'report_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CommunityDuaType::class,
            'status' => CommunityDuaStatus::class,
            'required_completions' => 'integer',
            'completion_count' => 'integer',
            'is_visible' => 'boolean',
            'fulfilled_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StripePayment, $this>
     */
    public function stripePayment(): BelongsTo
    {
        return $this->belongsTo(StripePayment::class);
    }

    /**
     * @return HasMany<CommunityDuaCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(CommunityDuaCompletion::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function completingUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_dua_completions')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function skippingUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'community_dua_skips')
            ->withTimestamps();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeQueueEligible(Builder $query): Builder
    {
        return $query
            ->where('status', CommunityDuaStatus::Active)
            ->where('is_visible', true);
    }

    public function displayName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isFullyCompleted(): bool
    {
        return $this->completion_count >= $this->required_completions;
    }

    public function isReported(): bool
    {
        return $this->status === CommunityDuaStatus::Reported;
    }
}
