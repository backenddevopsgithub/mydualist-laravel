// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    animateCardExit,
    appendSubmissionCards,
    cleanupCardAnimationClasses,
    clearEnterAnimationState,
    parseScrollMetaFromHtml,
    parseStatusTotal,
    prepareCardForExit,
    resolveFetchUrl,
    restartCssAnimation,
    syncEmptyState,
    syncSubmissionToggleButtons,
} from './list-submissions';

function createCard({ id = '1', status = 'pending', updating = 'false' } = {}) {
    const card = document.createElement('article');
    card.dataset.listSubmissionCard = '';
    card.dataset.submissionId = id;
    card.dataset.status = status;
    card.dataset.updating = updating;
    card.dataset.completeUrl = '/complete';
    card.dataset.undoUrl = '/undo';
    card.innerHTML = `
        <button data-submission-toggle="complete" type="button"></button>
        <button data-submission-toggle="undo" type="button" class="hidden"></button>
    `;

    return card;
}

function createScrollRoot({ total = 0, cards = [] } = {}) {
    const root = document.createElement('div');
    root.dataset.listSubmissionsScroll = '';
    root.dataset.statusTotal = String(total);

    const items = document.createElement('div');
    items.dataset.submissionsItems = '';
    cards.forEach((card) => items.append(card));
    root.append(items);

    document.body.append(root);

    return root;
}

describe('list-submissions animations', () => {
    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    it('restarts css animations by removing and re-adding the class', () => {
        const element = document.createElement('div');
        element.classList.add('animate-me');
        document.body.append(element);

        const widthSpy = vi.spyOn(element, 'offsetWidth', 'get').mockReturnValue(100);

        restartCssAnimation(element, 'animate-me');

        expect(element.classList.contains('animate-me')).toBe(true);
        expect(widthSpy).toHaveBeenCalled();
    });

    it('cleans up exit and pulse classes before replaying', () => {
        const card = createCard();
        card.classList.add('list-submission-card-exit-complete', 'list-submission-card--completed');
        card.querySelector('[data-submission-toggle="complete"]').classList.add('submission-toggle-pulse-complete');

        cleanupCardAnimationClasses(card);

        expect(card.classList.contains('list-submission-card-exit-complete')).toBe(false);
        expect(card.querySelector('.submission-toggle-pulse-complete')).toBeNull();
    });

    it('plays exit animations in both directions', async () => {
        vi.useFakeTimers();

        const completeCard = createCard({ status: 'pending' });
        const undoCard = createCard({ status: 'completed' });
        document.body.append(completeCard, undoCard);

        const completePromise = animateCardExit(completeCard, 'complete');
        const undoPromise = animateCardExit(undoCard, 'undo');

        vi.advanceTimersByTime(450);
        await Promise.all([completePromise, undoPromise]);

        expect(completeCard.classList.contains('list-submission-card-exit-complete')).toBe(true);
        expect(undoCard.classList.contains('list-submission-card-exit-undo')).toBe(true);

        vi.useRealTimers();
    });

    it('clears enter animation state so exit animations can take over', () => {
        const card = createCard({ status: 'completed' });
        const root = createScrollRoot({ total: 1, cards: [card] });
        const items = root.querySelector('[data-submissions-items]');
        items.classList.add('list-submissions-enter');

        prepareCardForExit(card);

        expect(items.classList.contains('list-submissions-enter')).toBe(false);
        expect(card.classList.contains('list-submission-card-exit-complete')).toBe(false);
    });

    it('resolves fetch urls to relative paths for same-app pagination', () => {
        const resolved = resolveFetchUrl('http://localhost/dashboard/lists/demo?page=2&status=completed');

        expect(resolved).toBe('/dashboard/lists/demo?page=2&status=completed');
        expect(resolveFetchUrl('/dashboard/lists/demo?page=3')).toBe('/dashboard/lists/demo?page=3');
    });

    it('parses scroll metadata embedded in load-more html', () => {
        const meta = parseScrollMetaFromHtml(`
            <article data-list-submission-card data-submission-id="9"></article>
            <template data-submissions-scroll-page-meta data-next-page-url="/dashboard/lists/demo?page=3" data-has-more="true"></template>
        `);

        expect(meta).toEqual({
            nextPageUrl: '/dashboard/lists/demo?page=3',
            hasMore: true,
        });
    });
});

describe('list-submissions counts and empty state', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('only shows empty state when status total and visible cards are zero', () => {
        const root = createScrollRoot({ total: 0 });

        syncEmptyState(root);

        expect(root.querySelector('[data-submissions-empty]')).not.toBeNull();

        const card = createCard();
        root.querySelector('[data-submissions-items]').append(card);
        root.dataset.statusTotal = '3';

        syncEmptyState(root);

        expect(root.querySelector('[data-submissions-empty]')).toBeNull();
    });

    it('parses status totals from scroll root metadata', () => {
        const root = document.createElement('div');
        root.dataset.statusTotal = '25';

        expect(parseStatusTotal(root)).toBe(25);
        expect(parseStatusTotal(null)).toBe(0);
    });
});

describe('list-submissions toggle wiring', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('disables toggle buttons while a card update is pending', () => {
        const card = createCard({ status: 'pending', updating: 'true' });
        document.body.append(card);

        syncSubmissionToggleButtons(card);

        expect(card.querySelector('[data-submission-toggle="complete"]').disabled).toBe(true);
        expect(card.querySelector('[data-submission-toggle="undo"]').disabled).toBe(true);
    });

    it('shows the correct toggle for completed and pending cards', () => {
        const pendingCard = createCard({ status: 'pending' });
        const completedCard = createCard({ status: 'completed' });
        document.body.append(pendingCard, completedCard);

        syncSubmissionToggleButtons(pendingCard);
        syncSubmissionToggleButtons(completedCard);

        expect(pendingCard.querySelector('[data-submission-toggle="complete"]').classList.contains('hidden')).toBe(false);
        expect(pendingCard.querySelector('[data-submission-toggle="undo"]').classList.contains('hidden')).toBe(true);
        expect(completedCard.querySelector('[data-submission-toggle="complete"]').classList.contains('hidden')).toBe(true);
        expect(completedCard.querySelector('[data-submission-toggle="undo"]').classList.contains('hidden')).toBe(false);
    });
});

describe('list-submissions infinite scroll append', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('appends new cards without duplicating existing submission ids', () => {
        const items = document.createElement('div');
        items.dataset.submissionsItems = '';
        items.append(createCard({ id: '1' }), createCard({ id: '2' }));

        appendSubmissionCards(items, `
            <article data-list-submission-card data-submission-id="2"></article>
            <article data-list-submission-card data-submission-id="3"></article>
            <article data-list-submission-card data-submission-id="4"></article>
        `);

        const ids = [...items.querySelectorAll('[data-list-submission-card]')].map((card) => card.dataset.submissionId);

        expect(ids).toEqual(['1', '2', '3', '4']);
    });
});
