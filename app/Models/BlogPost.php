<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogPost extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'read_time_minutes',
        'is_published',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'read_time_minutes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<BlogCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function featuredImageUrl(): string
    {
        if ($this->featured_image) {
            if (str_starts_with($this->featured_image, 'http')) {
                return $this->featured_image;
            }

            if (str_starts_with($this->featured_image, 'images/')) {
                return asset($this->featured_image);
            }

            return asset('storage/'.$this->featured_image);
        }

        return asset('images/blog/pilgrim.png');
    }
}
