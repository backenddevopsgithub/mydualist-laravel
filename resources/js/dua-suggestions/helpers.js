export function replaceNamePlaceholder(content, firstName) {
    const name = String(firstName ?? '').trim();

    if (name === '') {
        return content;
    }

    return String(content).replaceAll('{name}', name);
}

export function appendSuggestionText(current, suggestion) {
    const existing = String(current ?? '').trim();
    const next = String(suggestion ?? '').trim();

    if (next === '') {
        return existing;
    }

    return existing === '' ? next : `${existing}\n\n${next}`;
}

export function trackSuggestionId(selectedIds, id) {
    const numericId = Number(id);

    if (! Number.isInteger(numericId) || numericId <= 0) {
        return selectedIds;
    }

    if (selectedIds.includes(numericId)) {
        return selectedIds;
    }

    return [...selectedIds, numericId];
}

export function visibleSuggestionCount(total, expanded, limit = 4) {
    if (expanded) {
        return total;
    }

    return Math.min(limit, total);
}

export function mapSuggestionsResponse(payload) {
    const grouped = payload?.data?.general !== undefined
        || payload?.data?.quran !== undefined
        || payload?.data?.sunnah !== undefined
        ? payload.data
        : payload;

    return {
        general: grouped?.general ?? [],
        quran: grouped?.quran ?? [],
        sunnah: grouped?.sunnah ?? [],
    };
}

export function resolveActiveDuaIndex(activeDuaIndex, duasLength) {
    if (! Number.isInteger(duasLength) || duasLength <= 0) {
        return 0;
    }

    if (! Number.isInteger(activeDuaIndex) || activeDuaIndex < 0 || activeDuaIndex >= duasLength) {
        return 0;
    }

    return activeDuaIndex;
}

export function resolveActiveDuaIndexAfterRemoval(removedIndex, activeDuaIndex, duasLength) {
    if (! Number.isInteger(duasLength) || duasLength <= 0) {
        return 0;
    }

    let nextIndex = Number.isInteger(activeDuaIndex) ? activeDuaIndex : 0;

    if (nextIndex > removedIndex) {
        nextIndex--;
    }

    if (nextIndex >= duasLength) {
        nextIndex = duasLength - 1;
    }

    return Math.max(0, nextIndex);
}
