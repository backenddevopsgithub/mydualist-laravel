<?php

namespace App\Services\Blog;

use App\Services\BlogShortcodeTransformer;
use App\Services\Service;

class BlogContentNormalizer extends Service
{
    /**
     * @var list<string>
     */
    private const TRACKED_SHORTCODES = [
        'warning_box',
        'tip_box',
        'info_box',
        'comment_box',
        'success_box',
        'quranic_border',
        'hadith_border',
        'bismillah',
        'linebreak',
        'bullet_list',
        'pilgrim_faq',
        'popup_cta',
    ];

    public function __construct(
        private readonly BlogShortcodeTransformer $shortcodeTransformer,
    ) {}

    /**
     * @return array{content: string, broken_shortcodes: list<string>}
     */
    public function normalize(string $content): array
    {
        $content = $this->shortcodeTransformer->transform($content);
        $content = $this->removeEzTocMarkup($content);
        $content = $this->normalizeEmptyParagraphs($content);

        return [
            'content' => $content,
            'broken_shortcodes' => $this->detectRemainingShortcodes($content),
        ];
    }

    private function removeEzTocMarkup(string $content): string
    {
        $content = preg_replace('/<div[^>]*id="ez-toc-container"[^>]*>.*?<\/div>\s*<\/div>\s*<\/div>/is', '', $content) ?? $content;
        $content = preg_replace('/<div[^>]*class="[^"]*ez-toc-container[^"]*"[^>]*>.*?<\/nav>\s*<\/div>/is', '', $content) ?? $content;
        $content = preg_replace('/<span[^>]*class="[^"]*ez-toc-section[^"]*"[^>]*><\/span>/i', '', $content) ?? $content;
        $content = preg_replace('/<span[^>]*class="[^"]*ez-toc-section-end[^"]*"[^>]*><\/span>/i', '', $content) ?? $content;

        return trim($content);
    }

    private function normalizeEmptyParagraphs(string $content): string
    {
        $content = preg_replace('/<p>(?:\s|&nbsp;|\xc2\xa0)*<\/p>/iu', '', $content) ?? $content;

        return trim($content);
    }

    /**
     * @return list<string>
     */
    private function detectRemainingShortcodes(string $content): array
    {
        $found = [];

        foreach (self::TRACKED_SHORTCODES as $shortcode) {
            if (preg_match('/\['.preg_quote($shortcode, '/').'(?:\s|\]|\/)/i', $content)) {
                $found[] = $shortcode;
            }
        }

        return $found;
    }
}
