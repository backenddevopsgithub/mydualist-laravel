<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuaList extends Model
{
    /** @use HasFactory<\Database\Factories\DuaListFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'occasion',
        'start_date',
        'end_date',
        'cover_image_path',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'published_at' => 'datetime',
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
     * @return HasMany<DuaSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(DuaSubmission::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->end_date?->isPast() ?? false;
    }

    public function acceptsSubmissions(): bool
    {
        return $this->isActive()
            && ! $this->isExpired()
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function closedReason(): ?string
    {
        if ($this->isArchived()) {
            return 'This list is paused and is not accepting new dua requests right now.';
        }

        if ($this->isExpired()) {
            return 'This list has closed and is no longer accepting new dua requests.';
        }

        if (! $this->published_at || $this->published_at->isFuture()) {
            return 'This list is not open for submissions yet.';
        }

        return null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function publicUrl(): string
    {
        return route('dua-lists.public', $this);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function occasionLabel(): string
    {
        return Str::headline(str_replace('-', ' ', $this->occasion));
    }

    public function daysRemainingLabel(): ?string
    {
        if (! $this->end_date) {
            return null;
        }

        $days = now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);

        if ($days < 0) {
            return 'Ended';
        }

        return $days === 0 ? 'Ends today' : ((int) $days).'d left';
    }
}
