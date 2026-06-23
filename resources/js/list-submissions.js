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

async function toggleSubmissionCard(card) {
    if (card.dataset.updating === 'true') {
        return;
    }

    const previousStatus = card.dataset.status;
    const url = previousStatus === 'completed'
        ? card.dataset.undoUrl
        : card.dataset.completeUrl;
    const optimisticStatus = previousStatus === 'completed' ? 'pending' : 'completed';

    card.dataset.updating = 'true';
    card.dataset.status = optimisticStatus;
    syncSubmissionToggleButtons(card);

    try {
        const payload = await patchSubmissionStatus(url);
        card.dataset.status = payload?.data?.status ?? optimisticStatus;
    } catch (error) {
        card.dataset.status = previousStatus;
        window.alert(error instanceof Error ? error.message : 'Unable to update dua status.');
    } finally {
        card.dataset.updating = 'false';
        syncSubmissionToggleButtons(card);
    }
}

function initListSubmissionToggleDelegation() {
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-submission-toggle]');

        if (! button) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        const card = button.closest('[data-list-submission-card]');

        if (! card) {
            return;
        }

        toggleSubmissionCard(card);
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
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-list-submission-card]').forEach(syncSubmissionToggleButtons);
    });
}

export {
    initListSubmissionToggleDelegation,
    patchSubmissionStatus,
    registerListSubmissionActions,
    syncSubmissionToggleButtons,
    toggleSubmissionCard,
};
