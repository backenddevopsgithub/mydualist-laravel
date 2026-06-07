<?php

namespace App\Domains\Security\Services;

use App\Models\DuaList;
use App\Services\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PublicSubmissionSpamGuard extends Service
{
    private const DUPLICATE_WINDOW_SECONDS = 300;

    /**
     * @param  array{content?: string|null, duas?: array<int, string>|null, website?: string|null}  $data
     */
    public function inspect(Request $request, DuaList $duaList, array $data): void
    {
        if (filled($data['website'] ?? null)) {
            throw ValidationException::withMessages([
                'content' => 'Your dua request could not be submitted. Please try again.',
            ]);
        }

        $contents = $this->contents($data);
        $normalized = collect($contents)
            ->map(fn (string $content): string => $this->normalize($content));

        if ($normalized->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'duas' => 'Please remove duplicate dua requests before submitting.',
            ]);
        }

        foreach ($contents as $content) {
            if ($this->containsTooManyLinks($content)) {
                throw ValidationException::withMessages([
                    'duas' => 'Please remove extra links from your dua request.',
                ]);
            }

            $cacheKey = 'public-submission:duplicate:'.sha1($duaList->id.'|'.$request->ip().'|'.$this->normalize($content));

            if (! Cache::add($cacheKey, true, self::DUPLICATE_WINDOW_SECONDS)) {
                throw ValidationException::withMessages([
                    'duas' => 'This dua was already submitted recently. Please wait a moment before trying again.',
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
