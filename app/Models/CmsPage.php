<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'section',
        'excerpt',
        'content',
        'is_published',
        'published_at',
        'meta_title',
        'meta_description',
        'og_image_path',
        'canonical_url',
        'noindex',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'noindex' => 'boolean',
        ];
    }
}
