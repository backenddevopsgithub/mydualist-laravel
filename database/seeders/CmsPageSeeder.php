<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use App\Support\CmsPageSlugs;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => CmsPageSlugs::PRIVACY_POLICY,
                'title' => 'Privacy Policy',
                'section' => 'legal',
                'excerpt' => 'How My Dua List collects, uses, and protects your personal data.',
                'content' => '<p>This page is managed in the admin CMS. Add your privacy policy content in Filament under CMS Pages.</p>',
            ],
            [
                'slug' => CmsPageSlugs::TERMS_AND_CONDITIONS,
                'title' => 'Terms and Conditions',
                'section' => 'legal',
                'excerpt' => 'The terms that govern your use of My Dua List.',
                'content' => '<p>This page is managed in the admin CMS. Add your terms and conditions content in Filament under CMS Pages.</p>',
            ],
            [
                'slug' => CmsPageSlugs::HELP_AND_SUPPORT,
                'title' => 'Help and Support',
                'section' => 'support',
                'excerpt' => 'Get help using My Dua List and contact our support team.',
                'content' => '<p>This page is managed in the admin CMS. Add your help and support content in Filament under CMS Pages.</p>',
            ],
        ];

        foreach ($pages as $attributes) {
            CmsPage::query()->firstOrCreate(
                ['slug' => $attributes['slug']],
                [
                    ...$attributes,
                    'is_published' => true,
                    'published_at' => now(),
                ],
            );
        }
    }
}
