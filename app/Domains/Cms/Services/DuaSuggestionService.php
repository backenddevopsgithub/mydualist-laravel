<?php

namespace App\Domains\Cms\Services;

use App\Models\DuaList;
use App\Models\DuaSuggestion;
use App\Services\Service;
use Illuminate\Support\Collection;

class DuaSuggestionService extends Service
{
    /**
     * @return Collection<string, Collection<int, DuaSuggestion>>
     */
    public function getForList(DuaList $list): Collection
    {
        $suggestions = DuaSuggestion::query()
            ->where('is_visible', true)
            ->where(function ($query) use ($list): void {
                $query
                    ->where('category', '')
                    ->orWhere('category', $list->occasion);
            })
            ->orderBy('sort_order')
            ->orderByDesc('used_count')
            ->get();

        $grouped = $suggestions->groupBy(
            fn (DuaSuggestion $suggestion): string => $suggestion->source_type ?: 'general',
        );

        return collect([
            'general' => $grouped->get('general', collect())->values(),
            'quran' => $grouped->get('quran', collect())->values(),
            'sunnah' => $grouped->get('sunnah', collect())->values(),
        ]);
    }

    /**
     * @param  list<int|string>  $ids
     */
    public function incrementUsedCounts(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', array_filter($ids, fn (mixed $id): bool => is_numeric($id) && (int) $id > 0))));

        if ($ids === []) {
            return;
        }

        DuaSuggestion::query()
            ->whereIn('id', $ids)
            ->increment('used_count');
    }
}
