<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BlogPost extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'wp_post_id',
        'blog_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'faqs',
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
            'faqs' => 'array',
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
        $fallback = (string) config('mydualist.blog.fallback_featured_image_url', '');

        if (blank($this->featured_image)) {
            return $fallback !== '' ? $fallback : $this->defaultFeaturedImageUrl();
        }

        $image = $this->featured_image;

        if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
            return $image;
        }

        if (str_starts_with($image, 'images/') && is_file(public_path($image))) {
            return asset($image);
        }

        if (Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->url($image);
        }

        return $fallback !== '' ? $fallback : $this->defaultFeaturedImageUrl();
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    public function displayFaqs(): array
    {
        return collect($this->faqs ?? [])
            ->filter(fn (mixed $faq): bool => is_array($faq) && filled($faq['question'] ?? null) && filled($faq['answer'] ?? null))
            ->map(fn (array $faq): array => [
                'question' => (string) $faq['question'],
                'answer' => (string) $faq['answer'],
            ])
            ->values()
            ->all();
    }

    private function defaultFeaturedImageUrl(): string
    {
        return 'https://www.mydualist.com/wp-content/uploads/2024/09/Sheikh-Asim-Khan-Article-Three-3-Key-Insights-from-Pfidas-Shariah-Compliant-Home-Finance-Podcast-with-Sheikh-Asim-Khan-image-1.png-1.png';
    }
}
