<?php

namespace App\Http\Controllers;

use App\Domains\Cms\Services\CmsPageQueryService;
use App\Support\Seo\SeoPresenter;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CmsPageController extends Controller
{
    public function resolve(
        string $slug,
        CmsPageQueryService $cmsPages,
        PublicDuaListController $duaLists,
    ): View|Response {
        if ($cmsPages->exists($slug)) {
            return $this->show($slug, $cmsPages);
        }

        return app()->call([$duaLists, 'show'], ['duaList' => $slug]);
    }

    public function show(string $slug, CmsPageQueryService $cmsPages): View
    {
        $page = $cmsPages->findPublishedBySlug($slug);

        return view('cms.show', [
            'page' => $page,
            'seo' => SeoPresenter::forCmsPage($page),
        ]);
    }
}
