<?php

namespace App\Domains\Lists\Support;

use App\Models\DuaList;
use Illuminate\Support\Str;

class DuaListAvailability
{
    public function isExpired(DuaList $list): bool
    {
        if ($list->end_date === null) {
            return false;
        }

        return $list->end_date->startOfDay()->lt(now()->startOfDay());
    }

    public function isArchived(DuaList $list): bool
    {
        return $list->status === DuaList::STATUS_ARCHIVED;
    }

    public function isActive(DuaList $list): bool
    {
        return $list->status === DuaList::STATUS_ACTIVE;
    }

    public function isPublished(DuaList $list): bool
    {
        return $list->published_at !== null && $list->published_at->lte(now());
    }

    public function acceptsSubmissions(DuaList $list): bool
    {
        return $this->isActive($list)
            && ! $this->isExpired($list)
            && $this->isPublished($list);
    }

    public function closedReason(DuaList $list): ?string
    {
        if ($this->isArchived($list)) {
            return $this->publicClosedMessage($list);
        }

        if ($this->isExpired($list)) {
            return $this->publicClosedMessage($list);
        }

        if (! $list->published_at || $list->published_at->isFuture()) {
            return 'This list is not open for submissions yet.';
        }

        return null;
    }

    public function publicClosedMessage(DuaList $list): string
    {
        $owner = $list->user;
        $firstName = trim((string) ($owner?->first_name ?: Str::before((string) $owner?->name, ' '))) ?: 'The list owner';
        $possessive = $owner?->gender === 'female' ? 'her' : 'his';
        $occasion = $list->occasionLabel();
        $date = $list->start_date?->format('jS F Y') ?? 'upcoming date';

        return "{$firstName} is no longer accepting dua requests for {$possessive} {$occasion} trip on the {$date}. They may have received too many requests or had a change of plans.";
    }

    /**
     * @return 'active'|'closed'
     */
    public function dashboardAvailability(DuaList $list): string
    {
        return $this->acceptsSubmissions($list) ? 'active' : 'closed';
    }
}
