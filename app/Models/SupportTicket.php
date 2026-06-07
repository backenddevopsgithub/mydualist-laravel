<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'reason',
        'email',
        'first_name',
        'surname',
        'comments',
        'image_path',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
