<?php

namespace App\Services\Blog;

use App\Services\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BlogImageMigrator extends Service
{
    /**
     * @var array<string, string>
     */
    private array $urlToPath = [];

    private ?string $localBismillahPath = null;

    public function migrateContent(
        string $content,
        WordPressPostRecord $record,
        BlogImportReport $report,
    ): string {
        $this->ensureBismillahImage($report);

        foreach ($this->extractUrls($content) as $url) {
            $this->downloadToStorage($url, $record, $report);
        }

        return $this->rewriteContentUrls($content);
    }

    public function migrateFeaturedImage(
        ?string $url,
        WordPressPostRecord $record,
        BlogImportReport $report,
    ): ?string {
        if (blank($url)) {
            return null;
        }

        $this->ensureBismillahImage($report);

        return $this->downloadToStorage($url, $record, $report);
    }

    public function ensureBismillahImage(BlogImportReport $report): void
    {
        if ($this->localBismillahPath !== null) {
            return;
        }

        $remoteUrl = config('mydualist.blog.bismillah_image_url');

        if (! is_string($remoteUrl) || $remoteUrl === '') {
            return;
        }

        $path = $this->downloadToStorage($remoteUrl, null, $report, 'bismillah');

        if ($path !== null) {
            $this->localBismillahPath = $path;
        }
    }

    public function localBismillahUrl(): ?string
    {
        if ($this->localBismillahPath === null) {
            return null;
        }

        return asset('storage/'.$this->localBismillahPath);
    }

    private function downloadToStorage(
        string $url,
        ?WordPressPostRecord $record,
        BlogImportReport $report,
        ?string $basename = null,
    ): ?string {
        $normalizedUrl = $this->normalizeUrl($url);

        if (isset($this->urlToPath[$normalizedUrl])) {
            return $this->urlToPath[$normalizedUrl];
        }

        if (! $this->shouldMigrateUrl($normalizedUrl)) {
            return null;
        }

        try {
            $response = Http::timeout(30)->get($normalizedUrl);

            if (! $response->successful()) {
                if ($record !== null) {
                    $report->addMissingImage($record, $normalizedUrl);
                }

                return null;
            }

            $extension = $this->guessExtension($normalizedUrl, $response->header('Content-Type'));
            $filename = $basename !== null
                ? $basename.'.'.$extension
                : md5($normalizedUrl).'.'.$extension;
            $path = 'blog-images/'.$filename;

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $response->body());
            }

            $this->urlToPath[$normalizedUrl] = $path;

            return $path;
        } catch (\Throwable) {
            if ($record !== null) {
                $report->addMissingImage($record, $normalizedUrl);
            }

            return null;
        }
    }

    private function rewriteContentUrls(string $content): string
    {
        $content = $this->replaceKnownUrls($content);

        if ($this->localBismillahPath !== null) {
            $localUrl = asset('storage/'.$this->localBismillahPath);
            $remoteUrl = config('mydualist.blog.bismillah_image_url');

            if (is_string($remoteUrl) && $remoteUrl !== '') {
                $content = str_replace($remoteUrl, $localUrl, $content);
            }

            foreach ($this->wordpressOrigins() as $origin) {
                $content = preg_replace(
                    '#'.preg_quote($origin, '#').'/wp-content/uploads/[^"\'\s>]+#i',
                    $localUrl,
                    $content,
                ) ?? $content;
            }
        }

        return $content;
    }

    private function replaceKnownUrls(string $content): string
    {
        foreach ($this->urlToPath as $remoteUrl => $path) {
            $localUrl = asset('storage/'.$path);
            $content = str_replace($remoteUrl, $localUrl, $content);

            $pathOnly = parse_url($remoteUrl, PHP_URL_PATH);

            if (is_string($pathOnly) && $pathOnly !== '') {
                $content = str_replace($pathOnly, parse_url($localUrl, PHP_URL_PATH) ?: $localUrl, $content);
            }
        }

        return $content;
    }

    /**
     * @return list<string>
     */
    private function extractUrls(string $content): array
    {
        $urls = [];

        if (preg_match_all('/(?:src|href)=["\']([^"\']+)["\']/i', $content, $matches)) {
            foreach ($matches[1] as $url) {
                if ($this->shouldMigrateUrl($url)) {
                    $urls[] = $this->normalizeUrl($url);
                }
            }
        }

        if (preg_match_all('#https?://[^\s"\'<>]+#i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                if ($this->shouldMigrateUrl($url)) {
                    $urls[] = $this->normalizeUrl($url);
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function shouldMigrateUrl(string $url): bool
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://') && ! str_starts_with($url, '//')) {
            return false;
        }

        $normalized = $this->normalizeUrl($url);

        if (str_contains($normalized, '/wp-content/uploads/')) {
            return true;
        }

        $bismillahUrl = config('mydualist.blog.bismillah_image_url');

        return is_string($bismillahUrl) && ($normalized === $bismillahUrl || str_contains($normalized, 'bismillah'));
    }

    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return $url;
    }

    private function guessExtension(string $url, ?string $contentType): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        return match ($contentType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };
    }

    /**
     * @return list<string>
     */
    private function wordpressOrigins(): array
    {
        /** @var list<string> $origins */
        $origins = config('mydualist.blog.import.wordpress_origins', []);

        return $origins;
    }
}
