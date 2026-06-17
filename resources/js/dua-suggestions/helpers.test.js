import { describe, expect, it } from 'vitest';
import {
    appendSuggestionText,
    mapSuggestionsResponse,
    replaceNamePlaceholder,
    resolveActiveDuaIndex,
    resolveActiveDuaIndexAfterRemoval,
    trackSuggestionId,
    visibleSuggestionCount,
} from './helpers';

describe('replaceNamePlaceholder', () => {
    it('replaces {name} with the submitter first name', () => {
        expect(replaceNamePlaceholder('May Allah bless {name}.', 'Amina')).toBe('May Allah bless Amina.');
    });

    it('leaves content unchanged when first name is empty', () => {
        expect(replaceNamePlaceholder('May Allah bless {name}.', '   ')).toBe('May Allah bless {name}.');
    });
});

describe('appendSuggestionText', () => {
    it('inserts suggestion text into an empty textarea value', () => {
        expect(appendSuggestionText('', 'May Allah grant her health.')).toBe('May Allah grant her health.');
    });

    it('appends suggestion text with spacing when content already exists', () => {
        expect(appendSuggestionText('Existing dua.', 'Another dua.')).toBe("Existing dua.\n\nAnother dua.");
    });
});

describe('trackSuggestionId', () => {
    it('stores selected suggestion ids without duplicates', () => {
        expect(trackSuggestionId([], 4)).toEqual([4]);
        expect(trackSuggestionId([4], 4)).toEqual([4]);
        expect(trackSuggestionId([4], 9)).toEqual([4, 9]);
    });
});

describe('visibleSuggestionCount', () => {
    it('limits visible suggestions until expanded', () => {
        expect(visibleSuggestionCount(6, false, 4)).toBe(4);
        expect(visibleSuggestionCount(6, true, 4)).toBe(6);
    });
});

describe('mapSuggestionsResponse', () => {
    it('maps the public API envelope from fetch().json()', () => {
        const payload = {
            message: 'List suggestions retrieved.',
            data: {
                general: [],
                quran: [
                    { id: 3, title: 'Dua 31', content: 'Dua 33', source_type: 'quran' },
                    { id: 2, title: 'Quranic Dua', content: 'Rabbana atina fid dunya hasanah.', source_type: 'quran' },
                ],
                sunnah: [],
            },
        };

        expect(mapSuggestionsResponse(payload)).toEqual({
            general: [],
            quran: payload.data.quran,
            sunnah: [],
        });
    });

    it('does not expect axios-style response.data.data nesting', () => {
        const payload = {
            message: 'List suggestions retrieved.',
            data: {
                general: [],
                quran: [{ id: 2, title: 'Quranic Dua' }],
                sunnah: [],
            },
        };

        const mapped = mapSuggestionsResponse(payload);

        expect(mapped.quran).toHaveLength(1);
        expect(mapped.quran[0].title).toBe('Quranic Dua');
    });
});

describe('resolveActiveDuaIndex', () => {
    it('returns the active index when valid', () => {
        expect(resolveActiveDuaIndex(2, 3)).toBe(2);
    });

    it('falls back to the first field when the active index is invalid', () => {
        expect(resolveActiveDuaIndex(-1, 3)).toBe(0);
        expect(resolveActiveDuaIndex(5, 3)).toBe(0);
        expect(resolveActiveDuaIndex(Number.NaN, 3)).toBe(0);
    });
});

describe('resolveActiveDuaIndexAfterRemoval', () => {
    it('moves the active index down when an earlier field is removed', () => {
        expect(resolveActiveDuaIndexAfterRemoval(0, 2, 2)).toBe(1);
    });

    it('keeps the same slot when the active field is removed and another field shifts up', () => {
        expect(resolveActiveDuaIndexAfterRemoval(1, 1, 2)).toBe(1);
    });

    it('selects the nearest valid index when the active field was the last one', () => {
        expect(resolveActiveDuaIndexAfterRemoval(2, 2, 2)).toBe(1);
    });
});
