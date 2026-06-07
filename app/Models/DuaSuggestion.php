<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuaSuggestion extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'category',
        'content',
        'source_type',
        'source_reference',
        'is_visible',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
