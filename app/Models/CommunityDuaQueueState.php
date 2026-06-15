<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityDuaQueueState extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'showing_type',
        'pattern',
        'current_community_dua_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pattern' => 'integer',
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
     * @return BelongsTo<CommunityDua, $this>
     */
    public function currentCommunityDua(): BelongsTo
    {
        return $this->belongsTo(CommunityDua::class, 'current_community_dua_id');
    }
}
