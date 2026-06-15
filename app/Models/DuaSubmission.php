<?php

namespace App\Models;

use App\Enums\DuaSubmissionStatus;
use Database\Factories\DuaSubmissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DuaSubmission extends Model
{
    /** @use HasFactory<DuaSubmissionFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = DuaSubmissionStatus::Pending->value;

    public const STATUS_COMPLETED = DuaSubmissionStatus::Completed->value;

    public const STATUS_HIDDEN = DuaSubmissionStatus::Hidden->value;

    public const STATUS_ARCHIVED = DuaSubmissionStatus::Archived->value;

    public const STATUS_REPORTED = DuaSubmissionStatus::Reported->value;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dua_list_id',
        'user_id',
        'first_name',
        'last_name',
        'email',
        'gender',
        'is_anonymous',
        'is_personal_dua',
        'content',
        'note',
        'status',
        'completed_at',
        'completion_notified_at',
        'digest_sent_at',
        'hidden_at',
        'archived_at',
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
            'status' => DuaSubmissionStatus::class,
            'is_anonymous' => 'boolean',
            'is_personal_dua' => 'boolean',
            'completed_at' => 'datetime',
            'completion_notified_at' => 'datetime',
            'digest_sent_at' => 'datetime',
            'hidden_at' => 'datetime',
            'archived_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DuaList, $this>
     */
    public function duaList(): BelongsTo
    {
        return $this->belongsTo(DuaList::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            DuaSubmissionStatus::Pending->value,
            DuaSubmissionStatus::Completed->value,
        ]);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeStatus(Builder $query, DuaSubmissionStatus|string $status): Builder
    {
        $value = $status instanceof DuaSubmissionStatus ? $status->value : $status;

        return $query->where('status', $value);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePendingDigest(Builder $query): Builder
    {
        return $query
            ->whereNull('digest_sent_at')
            ->where('is_personal_dua', false);
    }

    public function displayName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        $name = trim((string) $this->first_name.' '.(string) $this->last_name);

        return $name !== '' ? $name : 'Anonymous';
    }

    public function isCompleted(): bool
    {
        return $this->status === DuaSubmissionStatus::Completed;
    }

    public function isHidden(): bool
    {
        return $this->status === DuaSubmissionStatus::Hidden;
    }

    public function isPersonalDua(): bool
    {
        return (bool) $this->is_personal_dua;
    }

    public function readableContent(): string
    {
        $words = preg_split('/\s+/u', trim($this->content), -1, PREG_SPLIT_NO_EMPTY);

        return collect($words)->map(function (string $word, int $index): string {
            $class = match ($index % 5) {
                0 => 'font-black text-stone-950',
                2 => 'font-semibold text-stone-800',
                4 => 'text-stone-500',
                default => 'text-stone-700',
            };

            return '<span class="'.$class.'">'.e($word).'</span>';
        })->implode(' ');
    }
}
