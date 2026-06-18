<?php

namespace App\Models;

use App\Enums\DuaSubmissionModerationAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuaSubmissionModerationLog extends Model
{
    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'dua_submission_id',
        'moderator_id',
        'action',
        'previous_status',
        'new_status',
        'notes',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => DuaSubmissionModerationAction::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<DuaSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(DuaSubmission::class, 'dua_submission_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
