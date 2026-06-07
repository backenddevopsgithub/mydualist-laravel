<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'scope',
        'route_name',
        'meta_title',
        'meta_description',
        'og_title',
        'og_description',
        'og_image_path',
        'twitter_card',
        'canonical_url',
        'noindex',
        'nofollow',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noindex' => 'boolean',
            'nofollow' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
