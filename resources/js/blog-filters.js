import { initInfiniteScroll } from './infinite-scroll';

const PARTIAL_ACCEPT = 'text/html+partial';

function updateCategoryButtons(activeCategory) {
    document.querySelectorAll('[data-blog-category]').forEach((button) => {
        const isActive = button.dataset.blogCategory === activeCategory;

        button.classList.toggle('bg-emerald-900', isActive);
        button.classList.toggle('text-white', isActive);
        button.classList.toggle('bg-white', ! isActive);
        button.classList.toggle('text-stone-700', ! isActive);
        button.classList.toggle('ring-1', ! isActive);
        button.classList.toggle('ring-stone-200', ! isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
}

function updateSearchCategoryInput(activeCategory) {
    const input = document.querySelector('[data-blog-search-category]');

    if (input) {
        input.value = activeCategory === 'all' ? '' : activeCategory;
    }
}

function replaceFeed(html) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const nextFeed = doc.querySelector('[data-blog-feed]');
    const currentFeed = document.querySelector('[data-blog-feed]');

    if (! nextFeed || ! currentFeed) {
        return;
    }

    currentFeed.replaceWith(nextFeed);
    initInfiniteScroll();
}

function buildFilterUrl(category, search) {
    const url = new URL(window.location.href);

    url.searchParams.delete('page');

    if (category && category !== 'all') {
        url.searchParams.set('category', category);
    } else {
        url.searchParams.delete('category');
    }

    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }

    return url;
}

async function loadCategory(category, search = '') {
    const url = buildFilterUrl(category, search);
    const feed = document.querySelector('[data-blog-feed]');

    if (feed) {
        feed.setAttribute('aria-busy', 'true');
    }

    try {
        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: PARTIAL_ACCEPT,
                'X-Blog-Filter': 'category',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(`Request failed with status ${response.status}`);
        }

        const html = await response.text();
        replaceFeed(html);
        updateCategoryButtons(category);
        updateSearchCategoryInput(category);
        window.history.pushState({ blogCategory: category, blogSearch: search }, '', url);
    } finally {
        if (feed) {
            feed.removeAttribute('aria-busy');
        }
    }
}

export function initBlogFilters() {
    const root = document.querySelector('[data-blog-filters]');

    if (! root) {
        return;
    }

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-blog-category]');

        if (! button) {
            return;
        }

        event.preventDefault();

        const category = button.dataset.blogCategory || 'all';
        const searchInput = document.querySelector('[data-blog-search-input]');
        const search = searchInput?.value?.trim() || '';

        loadCategory(category, search);
    });

    window.addEventListener('popstate', (event) => {
        const category = event.state?.blogCategory || new URL(window.location.href).searchParams.get('category') || 'all';
        const search = event.state?.blogSearch || new URL(window.location.href).searchParams.get('search') || '';

        loadCategory(category, search).catch(() => {
            window.location.reload();
        });
    });
}
