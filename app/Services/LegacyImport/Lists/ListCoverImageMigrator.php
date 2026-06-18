<?php

namespace App\Services\LegacyImport\Lists;

use App\Services\LegacyImport\LegacyImportReport;
use App\Services\LegacyImport\Lists\WordPressListRecord;
use App\Services\Service;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ListCoverImageMigrator extends Service
{
    /**
     * @var array<string, string>
     */
    private array $urlToPath = [];

    public function migrate(
        ?string $url,
        WordPressListRecord $record,
        LegacyImportReport $report,
    ): ?string {
        if (blank($url)) {
            return null;
        }

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
                $report->addMissingImage($record->summary(), $normalizedUrl);

                return null;
            }

            $extension = $this->guessExtension($normalizedUrl, $response->header('Content-Type'));
            $path = 'list-covers/'.$record->wpPostId.'.'.$extension;

            if (! Storage::disk('public')->exists($path)) {
                Storage::disk('public')->put($path, $response->body());
            }

            $this->urlToPath[$normalizedUrl] = $path;

            return $path;
        } catch (\Throwable) {
            $report->addMissingImage($record->summary(), $normalizedUrl);

            return null;
        }
    }

    private function shouldMigrateUrl(string $url): bool
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return false;
        }

        return str_contains($url, '/wp-content/uploads/') || str_contains($url, 'mydualist') || str_contains($url, 'thepilgrim');
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
}
