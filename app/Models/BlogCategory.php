<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'show_in_resources_filter',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_in_resources_filter' => 'boolean',
        ];
    }

    /**
     * @return HasMany<BlogPost, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForResourcesFilter($query)
    {
        return $query->where('show_in_resources_filter', true);
    }
}
