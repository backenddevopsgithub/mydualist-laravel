<?php

namespace App\Models;

use App\Support\DuaListOccasions;
use Database\Factories\DuaListFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DuaList extends Model
{
    /** @use HasFactory<DuaListFactory> */
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
        'dua_limit_per_person',
        'display_order',
        'email_frequency',
        'status',
        'published_at',
        'list_created_email_sent_at',
        'submission_quota_warning_sent_at',
        'no_activity_reminder_sent_at',
        'closing_soon_reminder_sent_at',
        'list_image_reminder_sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'dua_limit_per_person' => 'integer',
            'published_at' => 'datetime',
            'list_created_email_sent_at' => 'datetime',
            'submission_quota_warning_sent_at' => 'datetime',
            'no_activity_reminder_sent_at' => 'datetime',
            'closing_soon_reminder_sent_at' => 'datetime',
            'list_image_reminder_sent_at' => 'datetime',
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
            return $this->publicClosedMessage();
        }

        if ($this->isExpired()) {
            return $this->publicClosedMessage();
        }

        if (! $this->published_at || $this->published_at->isFuture()) {
            return 'This list is not open for submissions yet.';
        }

        return null;
    }

    public function publicClosedMessage(): string
    {
        $owner = $this->user;
        $firstName = trim((string) ($owner?->first_name ?: Str::before((string) $owner?->name, ' '))) ?: 'The list owner';
        $possessive = $owner?->gender === 'female' ? 'her' : 'his';
        $occasion = $this->occasionLabel();
        $date = $this->start_date?->format('jS F Y') ?? 'upcoming date';

        return "{$firstName} is no longer accepting dua requests for {$possessive} {$occasion} trip on the {$date}. They may have received too many requests or had a change of plans.";
    }

    public function publicInviteMessage(): string
    {
        $owner = $this->user;
        $firstName = trim((string) ($owner?->first_name ?: Str::before((string) $owner?->name, ' '))) ?: 'Someone';
        $possessive = $owner?->gender === 'female' ? 'her' : 'his';
        $occasion = $this->occasionLabel();
        $date = $this->start_date?->format('jS F Y') ?? 'upcoming date';

        return "{$firstName} invited you to share a dua request for {$possessive} {$occasion} trip on the {$date}.";
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function publicUrl(): string
    {
        return route('cms.show', $this);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function occasionLabel(): string
    {
        return DuaListOccasions::label($this->occasion);
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

    public function daysRemainingUntilEnd(): int
    {
        if (! $this->end_date) {
            return 0;
        }

        $days = now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);

        return max(0, (int) $days);
    }
}
