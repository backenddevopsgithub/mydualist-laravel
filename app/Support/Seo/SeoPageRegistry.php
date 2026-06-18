<?php

namespace App\Support\Seo;

use App\Models\CmsPage;
use App\Models\SeoMetadata;
use Illuminate\Support\Facades\Route;

class SeoPageRegistry
{
    /**
     * @return list<array{key: string, route: string, label: string, default_title: string, default_description: string, default_noindex?: bool}>
     */
    public static function staticPages(): array
    {
        return [
            [
                'key' => 'home',
                'route' => 'home',
                'label' => 'Homepage',
                'default_title' => 'The easiest way to collect dua requests',
                'default_description' => 'The easiest way to collect dua requests for Hajj, Umrah, and every occasion.',
            ],
            [
                'key' => 'blogs',
                'route' => 'blogs.index',
                'label' => 'Dua Resources (blog index)',
                'default_title' => 'Dua Resources',
                'default_description' => 'Browse dua resources, guides, and reminders from My Dua List.',
            ],
            [
                'key' => 'community-dua',
                'route' => 'community-dua.create',
                'label' => 'Submit a community dua',
                'default_title' => 'Submit a Community Dua',
                'default_description' => 'Share a community dua request with pilgrims on My Dua List.',
            ],
            [
                'key' => 'login',
                'route' => 'login',
                'label' => 'Login',
                'default_title' => 'Login',
                'default_description' => 'Sign in to manage your dua lists on My Dua List.',
                'default_noindex' => true,
            ],
        ];
    }

    public static function label(SeoMetadata $record): string
    {
        foreach (self::staticPages() as $page) {
            if ($record->scope === 'route' && $record->key === $page['key']) {
                return $page['label'];
            }
        }

        if ($record->scope === 'cms') {
            return CmsPage::query()->where('slug', $record->key)->value('title')
                ?: str($record->key)->headline()->toString();
        }

        if ($record->scope === 'blog') {
            return 'Blog: '.str($record->key)->headline()->toString();
        }

        return str($record->key)->headline()->toString();
    }

    public static function url(SeoMetadata $record): ?string
    {
        if ($record->canonical_url) {
            return $record->canonical_url;
        }

        if ($record->scope === 'route' && $record->route_name && Route::has($record->route_name)) {
            return route($record->route_name);
        }

        if ($record->scope === 'cms' && Route::has('cms.show')) {
            return route('cms.show', $record->key);
        }

        if ($record->scope === 'blog' && Route::has('blogs.show')) {
            return route('blogs.show', $record->key);
        }

        return null;
    }

    public static function editHint(SeoMetadata $record): ?string
    {
        if ($record->scope === 'cms') {
            return 'Also editable under CMS Pages.';
        }

        if ($record->scope === 'blog') {
            return 'Post content is edited under Blog Posts.';
        }

        return null;
    }
}
