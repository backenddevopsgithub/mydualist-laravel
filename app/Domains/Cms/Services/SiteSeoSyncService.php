<?php

namespace App\Domains\Cms\Services;

use App\Models\CmsPage;
use App\Models\SeoMetadata;
use App\Services\Service;
use App\Support\Seo\SeoPageRegistry;

class SiteSeoSyncService extends Service
{
    public function sync(): int
    {
        $created = 0;

        foreach (SeoPageRegistry::staticPages() as $page) {
            $record = SeoMetadata::query()->firstOrCreate(
                ['key' => $page['key']],
                [
                    'scope' => 'route',
                    'route_name' => $page['route'],
                    'meta_title' => $page['default_title'],
                    'meta_description' => $page['default_description'],
                    'og_title' => $page['default_title'],
                    'og_description' => $page['default_description'],
                    'twitter_card' => 'summary_large_image',
                    'noindex' => $page['default_noindex'] ?? false,
                ],
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        }

        CmsPage::query()->each(function (CmsPage $cmsPage) use (&$created): void {
            $record = SeoMetadata::query()->firstOrCreate(
                ['key' => $cmsPage->slug],
                [
                    'scope' => 'cms',
                    'route_name' => 'cms.show',
                    'meta_title' => $cmsPage->meta_title ?: $cmsPage->title,
                    'meta_description' => $cmsPage->meta_description ?: $cmsPage->excerpt,
                    'og_title' => $cmsPage->meta_title ?: $cmsPage->title,
                    'og_description' => $cmsPage->meta_description ?: $cmsPage->excerpt,
                    'og_image_path' => $cmsPage->og_image_path,
                    'canonical_url' => $cmsPage->canonical_url,
                    'noindex' => $cmsPage->noindex,
                    'twitter_card' => 'summary_large_image',
                ],
            );

            if ($record->wasRecentlyCreated) {
                $created++;
            }
        });

        return $created;
    }

    public function syncCmsPage(CmsPage $cmsPage): SeoMetadata
    {
        return SeoMetadata::query()->updateOrCreate(
            ['key' => $cmsPage->slug],
            [
                'scope' => 'cms',
                'route_name' => 'cms.show',
                'meta_title' => $cmsPage->meta_title,
                'meta_description' => $cmsPage->meta_description,
                'og_title' => $cmsPage->meta_title ?: $cmsPage->title,
                'og_description' => $cmsPage->meta_description ?: $cmsPage->excerpt,
                'og_image_path' => $cmsPage->og_image_path,
                'canonical_url' => $cmsPage->canonical_url,
                'noindex' => (bool) $cmsPage->noindex,
                'twitter_card' => 'summary_large_image',
            ],
        );
    }

    public function applyToCmsPage(SeoMetadata $metadata): void
    {
        if ($metadata->scope !== 'cms') {
            return;
        }

        CmsPage::query()
            ->where('slug', $metadata->key)
            ->update([
                'meta_title' => $metadata->meta_title,
                'meta_description' => $metadata->meta_description,
                'og_image_path' => $metadata->og_image_path,
                'canonical_url' => $metadata->canonical_url,
                'noindex' => (bool) $metadata->noindex,
            ]);
    }
}
