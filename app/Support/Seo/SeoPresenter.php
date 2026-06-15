<?php

namespace App\Support\Seo;

use App\Models\CmsPage;
use App\Models\SeoMetadata;
use Illuminate\Support\Facades\Storage;

class SeoPresenter
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $ogTitle,
        public readonly ?string $ogDescription,
        public readonly ?string $ogImageUrl,
        public readonly string $canonicalUrl,
        public readonly bool $noindex,
    ) {}

    public static function forCmsPage(CmsPage $page): self
    {
        $metadata = SeoMetadata::query()
            ->where('scope', 'cms')
            ->where('key', $page->slug)
            ->first();

        $title = $page->meta_title ?: $metadata?->meta_title ?: $page->title;
        $description = $page->meta_description ?: $metadata?->meta_description ?: $page->excerpt;
        $ogTitle = $metadata?->og_title ?: $title;
        $ogDescription = $metadata?->og_description ?: $description;
        $ogImageUrl = self::imageUrl($page->og_image_path ?: $metadata?->og_image_path);
        $canonicalUrl = $page->canonical_url
            ?: $metadata?->canonical_url
            ?: route('cms.show', $page->slug);

        return new self(
            title: $title,
            description: $description,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImageUrl: $ogImageUrl,
            canonicalUrl: $canonicalUrl,
            noindex: $page->noindex || (bool) ($metadata?->noindex ?? false),
        );
    }

    private static function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
