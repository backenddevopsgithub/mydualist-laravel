<?php

namespace App\Domains\Cms\Services;

use App\Models\CmsPage;
use App\Services\Service;
use App\Support\CmsPageSlugs;
use Illuminate\Support\Collection;

class CmsPageQueryService extends Service
{
    public function exists(string $slug): bool
    {
        return CmsPage::query()->where('slug', $slug)->exists();
    }

    public function findPublishedBySlug(string $slug): CmsPage
    {
        return CmsPage::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();
    }

    /**
     * @return Collection<int, CmsPage>
     */
    public function publishedFooterPages(): Collection
    {
        $pages = CmsPage::query()
            ->published()
            ->whereIn('slug', CmsPageSlugs::footerSlugs())
            ->get()
            ->keyBy('slug');

        return collect(CmsPageSlugs::footerSlugs())
            ->map(fn (string $slug): ?CmsPage => $pages->get($slug))
            ->filter()
            ->values();
    }
}
