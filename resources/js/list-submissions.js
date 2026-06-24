import './list-submissions.css';

const SCROLL_SELECTORS = {
    root: '[data-list-submissions-scroll]',
    items: '[data-submissions-items]',
    sentinel: '[data-submissions-scroll-sentinel]',
    loading: '[data-submissions-scroll-loading]',
    end: '[data-submissions-scroll-end]',
    error: '[data-submissions-scroll-error]',
    retry: '[data-submissions-scroll-retry]',
};

const cardRequestTokens = new WeakMap();
const scrollControllers = new WeakMap();

function resolveFetchUrl(url) {
    if (! url) {
        return '';
    }

    if (url.startsWith('/')) {
        return url;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        return `${parsed.pathname}${parsed.search}`;
    } catch {
        return url;
    }
}

function scrollNextPageUrl(scrollRoot) {
    return resolveFetchUrl(scrollRoot?.getAttribute('data-next-page-url') ?? '');
}

function setScrollNextPageUrl(scrollRoot, url) {
    if (! scrollRoot) {
        return;
    }

    const resolved = resolveFetchUrl(url);
    scrollRoot.setAttribute('data-next-page-url', resolved);
}

function parseScrollMetaFromHtml(html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    const meta = template.content.querySelector('[data-submissions-scroll-page-meta]');

    if (! meta) {
        return null;
    }

    return {
        nextPageUrl: resolveFetchUrl(meta.getAttribute('data-next-page-url') ?? ''),
        hasMore: meta.getAttribute('data-has-more') === 'true',
    };
}

function clearEnterAnimationState(scrollRoot) {
    scrollRoot?.querySelector(SCROLL_SELECTORS.items)?.classList.remove('list-submissions-enter');
}

function prepareCardForExit(card) {
    const scrollRoot = card.closest(SCROLL_SELECTORS.root);
    clearEnterAnimationState(scrollRoot);
    cleanupCardAnimationClasses(card);
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function patchSubmissionStatus(url, method = 'PATCH') {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        redirect: 'manual',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
        throw new Error('Unexpected page redirect while updating dua status.');
    }

    const payload = await response.json().catch(() => ({}));

    if (! response.ok) {
        throw new Error(payload?.message ?? 'Unable to update dua status.');
    }

    return payload;
}

function restartCssAnimation(element, className) {
    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
}

function cleanupCardAnimationClasses(card) {
    card.classList.remove(
        'list-submission-card-exit-complete',
        'list-submission-card-exit-undo',
        'list-submission-card--completed',
    );

    card.querySelectorAll('[data-submission-toggle]').forEach((button) => {
        button.classList.remove('submission-toggle-pulse-complete', 'submission-toggle-pulse-undo');
    });
}

function syncSubmissionToggleButtons(card) {
    const isCompleted = card.dataset.status === 'completed';

    card.querySelectorAll('[data-submission-toggle="complete"]').forEach((button) => {
        button.classList.toggle('hidden', isCompleted);
        button.disabled = card.dataset.updating === 'true';
    });

    card.querySelectorAll('[data-submission-toggle="undo"]').forEach((button) => {
        button.classList.toggle('hidden', ! isCompleted);
        button.disabled = card.dataset.updating === 'true';
    });
}

function pulseToggleButton(card, direction) {
    const selector = direction === 'complete'
        ? '[data-submission-toggle="complete"]'
        : '[data-submission-toggle="undo"]';
    const pulseClass = direction === 'complete'
        ? 'submission-toggle-pulse-complete'
        : 'submission-toggle-pulse-undo';
    const button = card.querySelector(selector);

    if (! button) {
        return;
    }

    button.classList.remove('submission-toggle-pulse-complete', 'submission-toggle-pulse-undo');
    restartCssAnimation(button, pulseClass);

    const cleanup = () => {
        button.classList.remove(pulseClass);
        button.removeEventListener('animationend', cleanup);
    };

    button.addEventListener('animationend', cleanup);
    globalThis.setTimeout(cleanup, 500);
}

function animateCardExit(card, direction) {
    return new Promise((resolve) => {
        if (! card.isConnected) {
            resolve();

            return;
        }

        prepareCardForExit(card);

        const exitClass = direction === 'complete'
            ? 'list-submission-card-exit-complete'
            : 'list-submission-card-exit-undo';

        if (direction === 'complete') {
            card.classList.add('list-submission-card--completed');
        }

        restartCssAnimation(card, exitClass);

        let settled = false;
        const finish = () => {
            if (settled) {
                return;
            }

            settled = true;
            card.removeEventListener('animationend', onAnimationEnd);
            card.removeEventListener('animationcancel', onAnimationEnd);
            resolve();
        };

        const onAnimationEnd = (event) => {
            if (event.target === card) {
                finish();
            }
        };

        card.addEventListener('animationend', onAnimationEnd);
        card.addEventListener('animationcancel', onAnimationEnd);
        globalThis.setTimeout(finish, 450);
    });
}

function submissionsListRoot() {
    return document.querySelector('[data-list-submissions-root]');
}

function submissionsListContainer() {
    return document.querySelector('[data-submissions-list]');
}

function submissionsScrollRoot() {
    return document.querySelector(SCROLL_SELECTORS.root);
}

function pageState() {
    const root = submissionsListRoot();

    if (! root || ! window.Alpine) {
        return null;
    }

    return window.Alpine.$data(root);
}

function parseStatusTotal(scrollRoot) {
    const value = Number(scrollRoot?.dataset.statusTotal ?? 0);

    return Number.isFinite(value) ? value : 0;
}

function setStatusTotal(scrollRoot, total) {
    if (! scrollRoot) {
        return;
    }

    scrollRoot.dataset.statusTotal = String(Math.max(0, total));
    const page = pageState();

    if (page?.setStatusTotal) {
        page.setStatusTotal(total);
    }
}

function visibleCardCount(scrollRoot) {
    return scrollRoot?.querySelectorAll('[data-list-submission-card]').length ?? 0;
}

function syncEmptyState(scrollRoot) {
    if (! scrollRoot) {
        return;
    }

    const items = scrollRoot.querySelector(SCROLL_SELECTORS.items);

    if (! items) {
        return;
    }

    const total = parseStatusTotal(scrollRoot);
    const visible = visibleCardCount(scrollRoot);
    const existing = items.querySelector('[data-submissions-empty]');

    if (total === 0 && visible === 0) {
        if (existing) {
            return;
        }

        const empty = document.createElement('div');
        empty.dataset.submissionsEmpty = '';
        empty.className = 'rounded-[2rem] border border-dashed border-emerald-950/15 bg-white p-10 text-center shadow-sm';
        empty.innerHTML = `
            <h2 class="text-2xl font-extrabold">No dua requests here yet</h2>
            <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-600">Share your list link or switch filters to review another status.</p>
        `;
        items.append(empty);

        return;
    }

    if (existing) {
        existing.remove();
    }
}

function playEnterAnimation(scrollRoot) {
    const items = scrollRoot?.querySelector(SCROLL_SELECTORS.items);

    if (! items) {
        return;
    }

    const status = scrollRoot.dataset.currentStatus ?? 'pending';
    items.style.setProperty(
        '--list-submission-enter-x',
        status === 'completed' ? '2.5rem' : '-2.5rem',
    );

    clearEnterAnimationState(scrollRoot);
    void items.offsetWidth;
    items.classList.add('list-submissions-enter');

    globalThis.setTimeout(() => {
        clearEnterAnimationState(scrollRoot);
    }, 520);

    globalThis.requestAnimationFrame(() => {
        items.querySelectorAll('[data-list-submission-card]').forEach((card) => {
            cleanupCardAnimationClasses(card);
        });
    });
}

function appendSubmissionCards(itemsRoot, html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    const loadedIds = new Set(
        [...itemsRoot.querySelectorAll('[data-list-submission-card]')].map((card) => card.dataset.submissionId),
    );

    template.content.querySelectorAll('[data-list-submission-card]').forEach((card) => {
        if (loadedIds.has(card.dataset.submissionId)) {
            return;
        }

        loadedIds.add(card.dataset.submissionId);
        itemsRoot.append(card);
    });
}

function probeSentinel(sentinel, loadMore) {
    if (! sentinel || typeof loadMore !== 'function') {
        return;
    }

    const rect = sentinel.getBoundingClientRect();
    const viewportHeight = window.innerHeight || document.documentElement.clientHeight;

    if (rect.top <= viewportHeight + 280) {
        loadMore();
    }
}

function destroyListSubmissionsScroll(scrollRoot) {
    const controller = scrollControllers.get(scrollRoot);

    if (! controller) {
        return;
    }

    controller.disconnect();
    scrollControllers.delete(scrollRoot);
}

function initListSubmissionsScroll(scrollRoot) {
    if (! scrollRoot) {
        return;
    }

    destroyListSubmissionsScroll(scrollRoot);

    const sentinel = scrollRoot.querySelector(SCROLL_SELECTORS.sentinel);
    const itemsRoot = scrollRoot.querySelector(SCROLL_SELECTORS.items);
    const loadingEl = scrollRoot.querySelector(SCROLL_SELECTORS.loading);
    const endEl = scrollRoot.querySelector(SCROLL_SELECTORS.end);
    const errorEl = scrollRoot.querySelector(SCROLL_SELECTORS.error);
    const retryBtn = scrollRoot.querySelector(SCROLL_SELECTORS.retry);

    if (! sentinel || ! itemsRoot) {
        return;
    }

    let nextPageUrl = scrollNextPageUrl(scrollRoot);
    let isLoading = false;
    let hasMore = nextPageUrl !== '';
    let observer = null;

    const show = (element) => element?.classList.remove('hidden');
    const hide = (element) => element?.classList.add('hidden');

    const finishWithoutMore = () => {
        hasMore = false;
        setScrollNextPageUrl(scrollRoot, '');
        observer?.disconnect();
        show(endEl);
    };

    if (! hasMore) {
        show(endEl);

        scrollControllers.set(scrollRoot, {
            disconnect: () => observer?.disconnect(),
            loadMore: async () => {},
        });

        return;
    }

    hide(endEl);

    const loadMore = async () => {
        if (isLoading || ! hasMore || ! nextPageUrl) {
            return;
        }

        isLoading = true;
        hide(errorEl);
        show(loadingEl);

        try {
            const response = await fetch(nextPageUrl, {
                headers: {
                    Accept: 'text/html+partial',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-List-Submissions-Load-More': '1',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const html = await response.text();
            appendSubmissionCards(itemsRoot, html);

            if (window.Alpine) {
                window.Alpine.initTree(itemsRoot);
            }

            itemsRoot.querySelectorAll('[data-list-submission-card]').forEach(syncSubmissionToggleButtons);

            const headerNextPage = resolveFetchUrl(response.headers.get('X-Infinite-Scroll-Next-Page') || '');
            const headerHasMore = response.headers.get('X-Infinite-Scroll-Has-More') === 'true';
            const htmlMeta = parseScrollMetaFromHtml(html);

            nextPageUrl = headerNextPage || htmlMeta?.nextPageUrl || '';
            hasMore = headerNextPage
                ? headerHasMore
                : (htmlMeta?.hasMore ?? nextPageUrl !== '');
            setScrollNextPageUrl(scrollRoot, nextPageUrl);

            if (! hasMore) {
                finishWithoutMore();
            }
        } catch (error) {
            show(errorEl);
        } finally {
            isLoading = false;
            hide(loadingEl);
        }
    };

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadMore();
            }
        },
        {
            root: null,
            rootMargin: '280px 0px',
            threshold: 0,
        },
    );

    observer.observe(sentinel);
    retryBtn?.addEventListener('click', loadMore);

    scrollControllers.set(scrollRoot, {
        disconnect: () => {
            observer?.disconnect();
            retryBtn?.removeEventListener('click', loadMore);
        },
        loadMore,
    });

    globalThis.requestAnimationFrame(() => {
        probeSentinel(sentinel, loadMore);
    });
}

function maybeLoadMoreAfterRemoval(scrollRoot) {
    const controller = scrollControllers.get(scrollRoot);

    if (! controller?.loadMore || ! scrollNextPageUrl(scrollRoot)) {
        return;
    }

    const visible = visibleCardCount(scrollRoot);

    if (visible > 3) {
        return;
    }

    controller.loadMore();
}

function bootstrapListSubmissionsScroll() {
    const scrollRoot = submissionsScrollRoot();

    if (! scrollRoot) {
        return;
    }

    initListSubmissionsScroll(scrollRoot);
    document.querySelectorAll('[data-list-submission-card]').forEach(syncSubmissionToggleButtons);
}

function mountSubmissionsMarkup(html) {
    const container = submissionsListContainer();

    if (! container) {
        return;
    }

    container.innerHTML = html;

    const scrollRoot = submissionsScrollRoot();

    if (scrollRoot) {
        playEnterAnimation(scrollRoot);
        bootstrapListSubmissionsScroll();
        syncEmptyState(scrollRoot);

        if (window.Alpine) {
            window.Alpine.initTree(scrollRoot);
        }
    }
}

async function handleCardStatusChange(detail) {
    const { card, direction, requestToken, statusCounts } = detail;
    const scrollRoot = submissionsScrollRoot();
    const page = pageState();

    if (cardRequestTokens.get(card) !== requestToken) {
        return;
    }

    if (page?.applyStatusDelta) {
        page.applyStatusDelta(direction);
    } else if (page?.applyStatusCounts) {
        page.applyStatusCounts(statusCounts);
    }

    if (scrollRoot) {
        const nextTotal = Math.max(0, parseStatusTotal(scrollRoot) - 1);
        setStatusTotal(scrollRoot, nextTotal);
    }

    pulseToggleButton(card, direction);
    await animateCardExit(card, direction);

    if (cardRequestTokens.get(card) !== requestToken || ! card.isConnected) {
        return;
    }

    card.remove();

    if (scrollRoot) {
        syncEmptyState(scrollRoot);
        maybeLoadMoreAfterRemoval(scrollRoot);
    }

    if (page?.reconcileStatusCounts) {
        page.reconcileStatusCounts(statusCounts);
    }
}

async function toggleSubmissionCard(card) {
    if (card.dataset.updating === 'true') {
        return;
    }

    const previousStatus = card.dataset.status;
    const url = previousStatus === 'completed'
        ? card.dataset.undoUrl
        : card.dataset.completeUrl;
    const direction = previousStatus === 'completed' ? 'undo' : 'complete';
    const requestToken = Symbol('submission-toggle');
    cardRequestTokens.set(card, requestToken);

    card.dataset.updating = 'true';
    card.querySelectorAll('[data-submission-toggle]').forEach((button) => {
        button.disabled = true;
    });

    try {
        const payload = await patchSubmissionStatus(url);

        if (cardRequestTokens.get(card) !== requestToken) {
            return;
        }

        const newStatus = payload?.data?.status ?? (direction === 'complete' ? 'completed' : 'pending');
        card.dataset.status = newStatus;

        document.dispatchEvent(new CustomEvent('list-submission:status-changed', {
            detail: {
                card,
                previousStatus,
                newStatus,
                direction,
                requestToken,
                statusCounts: payload?.meta?.status_counts,
            },
        }));
    } catch (error) {
        if (cardRequestTokens.get(card) === requestToken) {
            cardRequestTokens.delete(card);
            card.dataset.updating = 'false';
            syncSubmissionToggleButtons(card);
        }

        window.alert(error instanceof Error ? error.message : 'Unable to update dua status.');
    }
}

function initListSubmissionToggleDelegation() {
    if (document.documentElement.dataset.listSubmissionToggleBound === 'true') {
        return;
    }

    document.documentElement.dataset.listSubmissionToggleBound = 'true';

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-submission-toggle]');

        if (! button || button.disabled) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const card = button.closest('[data-list-submission-card]');

        if (! card || card.dataset.updating === 'true') {
            return;
        }

        toggleSubmissionCard(card);
    });

    document.addEventListener('list-submission:status-changed', (event) => {
        handleCardStatusChange(event.detail);
    });
}

function registerListSubmissionActions(Alpine) {
    Alpine.data('listSubmissionCard', (config) => ({
        reportOpen: false,
        reason: config.reason ?? '',

        init() {
            const card = this.$root;

            if (! card?.hasAttribute('data-list-submission-card')) {
                return;
            }

            card.dataset.status = config.status;
            card.dataset.completeUrl = config.completeUrl;
            card.dataset.undoUrl = config.undoUrl;
            syncSubmissionToggleButtons(card);
        },
    }));

    Alpine.data('listSubmissionsPage', (config) => ({
        currentStatus: config.currentStatus,
        statusCounts: { ...config.statusCounts },
        statusTotal: config.statusTotal ?? 0,
        listUrl: config.listUrl,
        switching: false,
        pendingToggleCount: 0,

        tabClass(status) {
            const active = this.currentStatus === status;

            return active
                ? '-mb-px border-b-2 border-emerald-950 px-2 pb-3 text-emerald-950 transition sm:px-4'
                : '-mb-px border-b-2 border-transparent px-2 pb-3 text-stone-400 transition hover:text-stone-700 sm:px-4';
        },

        countFor(status) {
            return this.statusCounts[status] ?? 0;
        },

        setStatusTotal(total) {
            this.statusTotal = Math.max(0, total);
        },

        applyStatusDelta(direction) {
            this.pendingToggleCount += 1;

            if (direction === 'complete') {
                this.statusCounts.pending = Math.max(0, (this.statusCounts.pending ?? 0) - 1);
                this.statusCounts.completed = (this.statusCounts.completed ?? 0) + 1;
            } else {
                this.statusCounts.completed = Math.max(0, (this.statusCounts.completed ?? 0) - 1);
                this.statusCounts.pending = (this.statusCounts.pending ?? 0) + 1;
            }

            if (
                (this.currentStatus === 'pending' && direction === 'complete')
                || (this.currentStatus === 'completed' && direction === 'undo')
            ) {
                this.statusTotal = Math.max(0, this.statusTotal - 1);
            }
        },

        reconcileStatusCounts(counts) {
            this.pendingToggleCount = Math.max(0, this.pendingToggleCount - 1);

            if (! counts || this.pendingToggleCount > 0) {
                return;
            }

            this.statusCounts = { ...counts };
        },

        applyStatusCounts(counts) {
            if (! counts) {
                return;
            }

            this.statusCounts = { ...this.statusCounts, ...counts };
        },

        async switchTab(status) {
            if (this.switching || status === this.currentStatus) {
                return;
            }

            this.switching = true;
            this.currentStatus = status;
            this.pendingToggleCount = 0;

            const url = new URL(this.listUrl, window.location.origin);
            url.searchParams.set('status', status);
            url.searchParams.delete('page');

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-List-Submissions-Partial': '1',
                    },
                    credentials: 'same-origin',
                });

                if (! response.ok) {
                    throw new Error('Unable to load submissions.');
                }

                const html = await response.text();
                const scrollRoot = submissionsScrollRoot();

                if (scrollRoot) {
                    destroyListSubmissionsScroll(scrollRoot);
                }

                mountSubmissionsMarkup(html);

                const nextScrollRoot = submissionsScrollRoot();

                if (nextScrollRoot) {
                    this.statusTotal = parseStatusTotal(nextScrollRoot);
                }

                window.history.replaceState({}, '', url);
            } catch (error) {
                window.location.assign(url);
            } finally {
                this.switching = false;
            }
        },

        init() {
            globalThis.requestAnimationFrame(() => {
                const scrollRoot = submissionsScrollRoot();

                if (scrollRoot) {
                    this.statusTotal = parseStatusTotal(scrollRoot);
                    bootstrapListSubmissionsScroll();
                }
            });
        },
    }));
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        if (! submissionsListRoot()) {
            document.querySelectorAll('[data-list-submission-card]').forEach(syncSubmissionToggleButtons);
        }
    });
}

export {
    animateCardExit,
    appendSubmissionCards,
    bootstrapListSubmissionsScroll,
    cleanupCardAnimationClasses,
    clearEnterAnimationState,
    initListSubmissionToggleDelegation,
    initListSubmissionsScroll,
    mountSubmissionsMarkup,
    parseScrollMetaFromHtml,
    parseStatusTotal,
    patchSubmissionStatus,
    playEnterAnimation,
    prepareCardForExit,
    probeSentinel,
    registerListSubmissionActions,
    resolveFetchUrl,
    restartCssAnimation,
    scrollNextPageUrl,
    syncEmptyState,
    syncSubmissionToggleButtons,
    toggleSubmissionCard,
};
