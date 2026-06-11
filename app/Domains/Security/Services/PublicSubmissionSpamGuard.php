<?php

namespace App\Domains\Security\Services;

use App\Models\DuaList;
use App\Services\Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicSubmissionSpamGuard extends Service
{
    private const DUPLICATE_WINDOW_SECONDS = 300;

    /**
     * @param  array{content?: string|null, duas?: array<int, string>|null, website?: string|null}  $data
     */
    public function inspect(DuaList $duaList, array $data, ?string $ipAddress = null): void
    {
        $ipAddress ??= 'unknown';

        if (filled($data['website'] ?? null)) {
            throw ValidationException::withMessages([
                'content' => 'Your dua request could not be submitted. Please try again.',
            ]);
        }

        $contents = $this->contents($data);
        $normalized = collect($contents)
            ->map(fn (string $content): string => $this->normalize($content));

        $seen = [];
        $duplicateMessages = [];

        foreach ($normalized as $index => $value) {
            if (isset($seen[$value])) {
                $number = $index + 1;
                $duplicateMessages['duas.'.$index] = "Dua {$number} looks like a duplicate. Please make each dua unique.";
            }

            $seen[$value] = true;
        }

        if ($duplicateMessages !== []) {
            throw ValidationException::withMessages($duplicateMessages);
        }

        foreach ($contents as $index => $content) {
            if ($this->containsTooManyLinks($content)) {
                throw ValidationException::withMessages([
                    'duas.'.$index => 'Dua '.($index + 1).' contains too many links. Please remove extra links.',
                ]);
            }

            $cacheKey = 'public-submission:duplicate:'.sha1($duaList->id.'|'.$ipAddress.'|'.$this->normalize($content));

            if (! Cache::add($cacheKey, true, self::DUPLICATE_WINDOW_SECONDS)) {
                throw ValidationException::withMessages([
                    'duas.'.$index => 'Dua '.($index + 1).' was already submitted recently. Please wait a moment before trying again.',
                ]);
            }
        }
    }

    /**
     * @param  array{content?: string|null, duas?: array<int, string>|null}  $data
     * @return list<string>
     */
    private function contents(array $data): array
    {
        return collect($data['duas'] ?? [$data['content'] ?? ''])
            ->map(fn (mixed $content): string => trim((string) $content))
            ->filter()
            ->values()
            ->all();
    }

    private function normalize(string $content): string
    {
        return Str::of($content)
            ->lower()
            ->squish()
            ->toString();
    }

    private function containsTooManyLinks(string $content): bool
    {
        return preg_match_all('/https?:\/\/|www\./i', $content) >= 3;
    }
}
