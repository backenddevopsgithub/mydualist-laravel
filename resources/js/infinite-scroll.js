const SELECTORS = {
    items: '[data-infinite-scroll-items]',
    sentinel: '[data-infinite-scroll-sentinel]',
    loading: '[data-infinite-scroll-loading]',
    end: '[data-infinite-scroll-end]',
    error: '[data-infinite-scroll-error]',
    retry: '[data-infinite-scroll-retry]',
    pagination: '[data-infinite-scroll-pagination-fallback]',
    feedItem: '[data-feed-item]',
    feedAdSlot: '[data-feed-ad-slot]',
};

/**
 * Insert a feed node at a specific index among feed items.
 * Supports future sponsored ad slots returned by the server.
 */
function insertAtFeedIndex(itemsRoot, node, targetIndex) {
    const items = [...itemsRoot.querySelectorAll(SELECTORS.feedItem)];
    const anchor = items.find((item) => Number(item.dataset.feedItemIndex) >= targetIndex);

    if (anchor) {
        itemsRoot.insertBefore(node, anchor);

        return;
    }

    itemsRoot.appendChild(node);
}

function appendFeedContent(itemsRoot, html) {
    const template = document.createElement('template');
    template.innerHTML = html.trim();

    const loadedIds = new Set(
        [...itemsRoot.querySelectorAll(SELECTORS.feedItem)].map((item) => item.dataset.feedItemId),
    );

    template.content.querySelectorAll(SELECTORS.feedItem).forEach((item) => {
        if (loadedIds.has(item.dataset.feedItemId)) {
            return;
        }

        loadedIds.add(item.dataset.feedItemId);
        itemsRoot.appendChild(item);
    });

    template.content.querySelectorAll(SELECTORS.feedAdSlot).forEach((slot) => {
        const adIndex = Number(slot.dataset.feedAdIndex);

        if (Number.isNaN(adIndex)) {
            itemsRoot.appendChild(slot);

            return;
        }

        insertAtFeedIndex(itemsRoot, slot, adIndex);
    });
}

function updateBrowserUrl(container, page) {
    if (! page) {
        return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('page', page);

    window.history.replaceState({ infiniteScrollPage: page }, '', url);
}

function initInfiniteScrollContainer(container) {
    const itemsRoot = container.querySelector(SELECTORS.items);
    const sentinel = container.querySelector(SELECTORS.sentinel);
    const loadingEl = container.querySelector(SELECTORS.loading);
    const endEl = container.querySelector(SELECTORS.end);
    const errorEl = container.querySelector(SELECTORS.error);
    const retryBtn = container.querySelector(SELECTORS.retry);

    if (! itemsRoot || ! sentinel) {
        return;
    }

    let nextPageUrl = container.dataset.nextPageUrl || '';
    let isLoading = false;
    let hasMore = nextPageUrl !== '';
    let observer = null;

    const show = (element) => element?.classList.remove('hidden');
    const hide = (element) => element?.classList.add('hidden');

    const showLoading = () => show(loadingEl);
    const hideLoading = () => hide(loadingEl);
    const showEnd = () => show(endEl);
    const showError = () => show(errorEl);
    const hideError = () => hide(errorEl);

    const finishWithoutMore = () => {
        hasMore = false;
        container.dataset.nextPageUrl = '';
        observer?.disconnect();
        showEnd();
    };

    if (! hasMore) {
        return;
    }

    container.classList.add('infinite-scroll-enabled');

    const loadMore = async () => {
        if (isLoading || ! hasMore || ! nextPageUrl) {
            return;
        }

        isLoading = true;
        hideError();
        showLoading();

        try {
            const response = await fetch(nextPageUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html+partial',
                },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const html = await response.text();
            appendFeedContent(itemsRoot, html);

            nextPageUrl = response.headers.get('X-Infinite-Scroll-Next-Page') || '';
            hasMore = response.headers.get('X-Infinite-Scroll-Has-More') === 'true';
            container.dataset.nextPageUrl = nextPageUrl;

            updateBrowserUrl(container, response.headers.get('X-Infinite-Scroll-Page'));

            if (! hasMore) {
                finishWithoutMore();
            }
        } catch (error) {
            showError();
        } finally {
            isLoading = false;
            hideLoading();
        }
    };

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadMore();
            }
        },
        { rootMargin: '240px 0px' },
    );

    observer.observe(sentinel);
    retryBtn?.addEventListener('click', loadMore);
}

export function initInfiniteScroll() {
    document.querySelectorAll('[data-infinite-scroll]').forEach(initInfiniteScrollContainer);
}
