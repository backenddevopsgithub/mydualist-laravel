<?php

namespace App\Services;

use App\Support\BlogShortcodeHtml;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogShortcodeTransformer extends Service
{
    /**
     * @var list<string>
     */
    private const PAIRED_SHORTCODES = [
        'warning_box',
        'tip_box',
        'info_box',
        'comment_box',
        'success_box',
        'quranic_border',
        'hadith_border',
        'bullet_list',
        'pilgrim_faq',
    ];

    /**
     * @var list<string>
     */
    private const MALFORM_REPAIR_TAGS = [
        'hadith_border',
        'quranic_border',
        'warning_box',
        'tip_box',
        'info_box',
        'comment_box',
        'success_box',
        'bullet_list',
        'pilgrim_faq',
    ];

    public function transform(string $content): string
    {
        $content = $this->repairUnclosedShortcodes($content);

        $content = $this->replaceSelfClosingShortcodes($content);

        $maxPasses = 50;

        for ($pass = 0; $pass < $maxPasses; $pass++) {
            $updated = $this->transformPairedShortcodesOnce($content);

            if ($updated === $content) {
                break;
            }

            $content = $updated;
        }

        return $content;
    }

    private function repairUnclosedShortcodes(string $content): string
    {
        foreach (self::MALFORM_REPAIR_TAGS as $tag) {
            $openCount = preg_match_all('/\['.preg_quote($tag, '/').'(?:\s[^\]]*)?\]/i', $content) ?: 0;
            $closeCount = substr_count(strtolower($content), '[/'.$tag.']');

            $missing = $openCount - $closeCount;

            if ($missing <= 0) {
                continue;
            }

            Log::warning('BlogShortcodeTransformer repaired unclosed shortcode tags.', [
                'tag' => $tag,
                'missing_closing_tags' => $missing,
            ]);

            $content .= str_repeat('[/'. $tag.']', $missing);
        }

        return $content;
    }

    private function replaceSelfClosingShortcodes(string $content): string
    {
        $content = preg_replace('/\[bismillah\]\s*(?:\[\/bismillah\])?/i', BlogShortcodeHtml::bismillah(), $content) ?? $content;
        $content = preg_replace('/\[linebreak\]\s*(?:\[\/linebreak\])?/i', BlogShortcodeHtml::linebreak(), $content) ?? $content;

        return $content;
    }

    private function transformPairedShortcodesOnce(string $content): string
    {
        foreach (self::PAIRED_SHORTCODES as $tag) {
            $pattern = '/\['.preg_quote($tag, '/').'([^\]]*)\](.*?)\[\/'.preg_quote($tag, '/').'\]/is';

            $content = preg_replace_callback($pattern, function (array $matches) use ($tag): string {
                $attributes = $this->parseAttributes($matches[1]);
                $inner = $matches[2];

                return match ($tag) {
                    'warning_box' => BlogShortcodeHtml::warningBox($this->plainText($inner)),
                    'tip_box' => BlogShortcodeHtml::tipBox($this->plainText($inner)),
                    'info_box' => BlogShortcodeHtml::infoBox($this->plainText($inner)),
                    'comment_box' => BlogShortcodeHtml::commentBox($this->plainText($inner)),
                    'success_box' => BlogShortcodeHtml::successBox($this->plainText($inner)),
                    'quranic_border' => BlogShortcodeHtml::quranicBorder($inner),
                    'hadith_border' => BlogShortcodeHtml::hadithBorder($inner),
                    'bullet_list' => BlogShortcodeHtml::bulletList(
                        $attributes['bullet_no'] ?? '',
                        $this->plainText($inner),
                    ),
                    'pilgrim_faq' => BlogShortcodeHtml::pilgrimFaq(
                        $this->faqId($attributes['title'] ?? null),
                        $this->plainText($attributes['title'] ?? 'Title'),
                        $this->plainText($inner),
                    ),
                    default => $matches[0],
                };
            }, $content) ?? $content;
        }

        return $content;
    }

    /**
     * @return array<string, string>
     */
    private function parseAttributes(string $attributeString): array
    {
        $attributes = [];

        if (preg_match_all('/(\w+)\s*=\s*"([^"]*)"/', $attributeString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attributes[$match[1]] = $match[2];
            }
        }

        return $attributes;
    }

    private function plainText(string $value): string
    {
        return trim(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function faqId(?string $title): string
    {
        if ($title === null || trim($title) === '') {
            return 'faq-'.Str::lower(Str::random(8));
        }

        $id = strtolower(str_replace(' ', '', strip_tags($title)));

        return $id !== '' ? $id : 'faq-'.Str::lower(Str::random(8));
    }
}
