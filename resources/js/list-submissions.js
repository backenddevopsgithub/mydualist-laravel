function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function patchSubmissionStatus(url, method = 'PATCH') {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    const payload = await response.json().catch(() => ({}));

    if (! response.ok) {
        throw new Error(payload?.message ?? 'Unable to update dua status.');
    }

    return payload;
}

function registerListSubmissionActions(Alpine) {
    Alpine.data('listSubmissionCard', (config) => ({
        status: config.status,
        updating: false,
        reportOpen: false,
        reason: '',

        async toggleCompletion() {
            if (this.updating) {
                return;
            }

            this.updating = true;

            const url = this.status === 'completed'
                ? config.undoUrl
                : config.completeUrl;

            try {
                const payload = await patchSubmissionStatus(url);
                this.status = payload?.data?.status ?? (this.status === 'completed' ? 'pending' : 'completed');
            } catch (error) {
                window.alert(error instanceof Error ? error.message : 'Unable to update dua status.');
            } finally {
                this.updating = false;
            }
        },
    }));
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.Alpine) {
        registerListSubmissionActions(window.Alpine);
    }
});

export { patchSubmissionStatus, registerListSubmissionActions };
