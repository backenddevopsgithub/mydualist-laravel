<?php

namespace App\Models;

use App\Domains\Lists\Support\DuaListAvailability;
use App\Enums\DuaSubmissionStatus;
use App\Support\DuaListOccasions;
use App\Support\CreatorMode;
use App\Support\TrackableDonationLink;
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
        'wp_post_id',
        'user_id',
        'title',
        'slug',
        'occasion',
        'start_date',
        'end_date',
        'cover_image_path',
        'list_mode',
        'donation_link',
        'donation_note',
        'insights_views',
        'insights_clicks',
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
            'wp_post_id' => 'integer',
            'published_at' => 'datetime',
            'list_created_email_sent_at' => 'datetime',
            'submission_quota_warning_sent_at' => 'datetime',
            'no_activity_reminder_sent_at' => 'datetime',
            'closing_soon_reminder_sent_at' => 'datetime',
            'list_image_reminder_sent_at' => 'datetime',
            'insights_views' => 'integer',
            'insights_clicks' => 'integer',
            'submissions_count' => 'integer',
            'pending_submissions_count' => 'integer',
            'completed_submissions_count' => 'integer',
            'hidden_submissions_count' => 'integer',
            'archived_submissions_count' => 'integer',
            'reported_submissions_count' => 'integer',
            'non_personal_submissions_count' => 'integer',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function statusCountMap(): array
    {
        return [
            DuaSubmissionStatus::Pending->value => (int) $this->pending_submissions_count,
            DuaSubmissionStatus::Completed->value => (int) $this->completed_submissions_count,
            DuaSubmissionStatus::Hidden->value => (int) $this->hidden_submissions_count,
            DuaSubmissionStatus::Archived->value => (int) $this->archived_submissions_count,
            DuaSubmissionStatus::Reported->value => (int) $this->reported_submissions_count,
        ];
    }

    public function isCreatorList(): bool
    {
        return CreatorMode::isCreatorList($this);
    }

    public function showsCreatorFeatures(): bool
    {
        return CreatorMode::showsCreatorFeatures($this);
    }

    public function hasFundraisingContent(): bool
    {
        return CreatorMode::hasFundraisingContent($this);
    }

    public function trackableDonationUrl(): ?string
    {
        if (! filled($this->donation_link)) {
            return null;
        }

        return TrackableDonationLink::forList($this, (string) $this->donation_link);
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

    public function availability(): DuaListAvailability
    {
        return app(DuaListAvailability::class);
    }

    public function isExpired(): bool
    {
        return $this->availability()->isExpired($this);
    }

    public function acceptsSubmissions(): bool
    {
        return $this->availability()->acceptsSubmissions($this);
    }

    public function closedReason(): ?string
    {
        return $this->availability()->closedReason($this);
    }

    public function publicClosedMessage(): string
    {
        return $this->availability()->publicClosedMessage($this);
    }

    /**
     * @return 'active'|'closed'
     */
    public function dashboardAvailability(): string
    {
        return $this->availability()->dashboardAvailability($this);
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

        return (int) $days === 0 ? 'Ends today' : ((int) $days).'d left';
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
