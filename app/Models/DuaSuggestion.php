<?php

namespace App\Models;

use Database\Factories\DuaSuggestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuaSuggestion extends Model
{
    /** @use HasFactory<DuaSuggestionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'wp_post_id',
        'title',
        'category',
        'content',
        'source_type',
        'source_reference',
        'is_visible',
        'sort_order',
        'used_count',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'used_count' => 'integer',
            'wp_post_id' => 'integer',
        ];
    }
}
