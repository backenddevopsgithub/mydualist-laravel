<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityDuaCompletion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'community_dua_id',
        'user_id',
    ];

    /**
     * @return BelongsTo<CommunityDua, $this>
     */
    public function communityDua(): BelongsTo
    {
        return $this->belongsTo(CommunityDua::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
