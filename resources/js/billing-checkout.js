import { loadStripe } from '@stripe/stripe-js';
import { processPaymentSubmission } from './billing-checkout/payment-flow';

const root = document.getElementById('billing-checkout-root');

if (! root) {
    throw new Error('Billing checkout root element is missing.');
}

const purchaseId = root.dataset.purchaseId;
const stripeKey = root.dataset.stripeKey;
const returnUrl = root.dataset.returnUrl;
const apiBase = root.dataset.apiBase;
const successUrl = root.dataset.successUrl;
const failureUrl = root.dataset.failureUrl;
const continueLabel = root.dataset.continueLabel;

const loadingEl = document.getElementById('checkout-loading');
const errorEl = document.getElementById('checkout-error');
const errorMessageEl = document.getElementById('checkout-error-message');
const completedEl = document.getElementById('checkout-completed');
const completedProductEl = document.getElementById('checkout-completed-product');
const formEl = document.getElementById('checkout-form');
const productNameEl = document.getElementById('checkout-product-name');
const amountEl = document.getElementById('checkout-amount');
const paymentForm = document.getElementById('payment-form');
const paymentMessageEl = document.getElementById('payment-message');
const submitButton = document.getElementById('submit-button');
const paymentElementContainer = document.getElementById('payment-element');

const apiFetch = async (url, options = {}) => {
    const response = await fetch(url, {
        credentials: 'include',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers,
        },
        ...options,
    });

    const data = await response.json().catch(() => ({}));

    if (! response.ok) {
        const error = new Error(data.message ?? 'Request failed.');
        error.response = { status: response.status, data };
        throw error;
    }

    return data;
};

const formatAmount = (amountMinor, currency) => {
    const amount = amountMinor / 100;

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currency.toUpperCase(),
        }).format(amount);
    } catch {
        return `${amount.toFixed(2)} ${currency.toUpperCase()}`;
    }
};

const show = (element) => {
    element?.classList.remove('hidden');
};

const hide = (element) => {
    element?.classList.add('hidden');
};

const showError = (message) => {
    hide(loadingEl);
    hide(formEl);
    hide(completedEl);

    if (errorMessageEl) {
        errorMessageEl.textContent = message;
    }

    applyCheckoutLinks();
    show(errorEl);
};

const applyCheckoutLinks = (purchase = {}) => {
    const continueLink = document.getElementById('checkout-continue-link');
    const failureLink = document.getElementById('checkout-failure-link');
    const resolvedSuccessUrl = purchase.success_url || successUrl;
    const resolvedFailureUrl = purchase.failure_url || failureUrl;
    const resolvedLabel = purchase.continue_label || continueLabel;

    if (continueLink && resolvedSuccessUrl) {
        continueLink.href = resolvedSuccessUrl;
    }

    if (continueLink && resolvedLabel) {
        continueLink.textContent = resolvedLabel;
    }

    if (failureLink && resolvedFailureUrl) {
        failureLink.href = resolvedFailureUrl;
    }
};

const redirectToSuccess = (purchase = {}) => {
    const target = purchase.success_url || successUrl;

    if (target) {
        window.setTimeout(() => {
            window.location.href = target;
        }, 1200);
    }
};

const showCompleted = (purchase) => {
    hide(loadingEl);
    hide(formEl);
    hide(errorEl);

    if (completedProductEl) {
        completedProductEl.textContent = purchase.product_name ?? purchase.product_code ?? 'Purchase';
    }

    applyCheckoutLinks(purchase);
    show(completedEl);
    redirectToSuccess(purchase);
};

const PAYMENT_STATUS_POLL_INTERVAL_MS = 4000;
const PAYMENT_STATUS_MAX_ATTEMPTS = 30;

const isTerminalPaymentStatus = (status) => {
    if (! status) {
        return false;
    }

    if (status.is_completed) {
        return true;
    }

    return ['failed', 'canceled'].includes(status.status);
};

const extractApiError = (error) => {
    if (error?.response?.data?.message) {
        return error.response.data.message;
    }

    return 'Something went wrong while loading checkout.';
};

const pollPaymentStatus = async (
    maxAttempts = PAYMENT_STATUS_MAX_ATTEMPTS,
    intervalMs = PAYMENT_STATUS_POLL_INTERVAL_MS,
) => {
    for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
        const payload = await apiFetch(`${apiBase}/payment-status`);
        const status = payload?.data;

        if (isTerminalPaymentStatus(status)) {
            return status;
        }

        if (attempt < maxAttempts - 1) {
            await new Promise((resolve) => setTimeout(resolve, intervalMs));
        }
    }

    return null;
};

const handleRedirectReturn = async () => {
    const params = new URLSearchParams(window.location.search);
    const redirectStatus = params.get('redirect_status');

    if (! redirectStatus) {
        return false;
    }

    if (redirectStatus === 'succeeded') {
        const status = await pollPaymentStatus();

        if (status?.is_completed) {
            const purchase = await apiFetch(apiBase);
            showCompleted(purchase?.data ?? {});

            return true;
        }

        if (status?.status === 'failed' || status?.status === 'canceled') {
            showError(status.failure_reason ?? 'Your payment could not be completed.');
            return true;
        }

        showError('Payment was submitted but confirmation is still pending. Please refresh in a moment.');
        return true;
    }

    if (redirectStatus === 'failed') {
        showError('Your payment could not be completed. Please try again.');
        return true;
    }

    return false;
};

const mountPaymentElement = async (purchase) => {
    const secretPayload = await apiFetch(`${apiBase}/client-secret`);
    const clientSecret = secretPayload?.data?.client_secret;

    if (! clientSecret) {
        throw new Error('Payment could not be initialized.');
    }

    const stripe = await loadStripe(stripeKey);

    if (! stripe) {
        throw new Error('Stripe failed to load.');
    }

    const elements = stripe.elements({
        clientSecret,
        appearance: {
            theme: 'stripe',
            variables: {
                colorPrimary: '#064e3b',
                borderRadius: '16px',
            },
        },
    });

    const paymentElement = elements.create('payment', {
        layout: 'tabs',
        wallets: {
            applePay: 'auto',
            googlePay: 'auto',
        },
    });

    paymentElement.mount(paymentElementContainer);

    paymentForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (paymentMessageEl) {
            paymentMessageEl.textContent = '';
            hide(paymentMessageEl);
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';

        const result = await processPaymentSubmission({
            elements,
            stripe,
            clientSecret,
            returnUrl,
        });

        if (result.stage === 'submit') {
            if (paymentMessageEl) {
                paymentMessageEl.textContent = result.error?.message ?? 'Please check your payment details.';
                show(paymentMessageEl);
            }

            submitButton.disabled = false;
            submitButton.textContent = 'Pay now';

            return;
        }

        if (result.stage === 'confirm') {
            if (paymentMessageEl) {
                paymentMessageEl.textContent = result.error?.message ?? 'Payment failed.';
                show(paymentMessageEl);
            }

            submitButton.disabled = false;
            submitButton.textContent = 'Pay now';
        }
    });
};

const initializeCheckout = async () => {
    try {
        if (await handleRedirectReturn()) {
            return;
        }

        const purchasePayload = await apiFetch(apiBase);
        const purchase = purchasePayload?.data;

        if (! purchase) {
            throw new Error('Purchase details could not be loaded.');
        }

        hide(loadingEl);

        if (purchase.is_completed) {
            showCompleted(purchase);
            return;
        }

        if (! purchase.is_payable) {
            showError(purchase.failure_reason ?? 'This purchase is no longer payable.');
            return;
        }

        if (productNameEl) {
            productNameEl.textContent = purchase.product_name ?? purchase.product_code;
        }

        if (amountEl) {
            amountEl.textContent = formatAmount(purchase.amount_minor, purchase.currency);
        }

        show(formEl);
        await mountPaymentElement(purchase);
    } catch (error) {
        showError(extractApiError(error));
    }
};

initializeCheckout();
