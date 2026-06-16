import { describe, expect, it, vi } from 'vitest';
import { processPaymentSubmission } from './payment-flow';

const clientSecret = 'pi_test_secret';
const returnUrl = 'https://example.test/billing/purchases/1/checkout';

function createMocks({ submitError = null, confirmError = null } = {}) {
    const elements = {
        submit: vi.fn().mockResolvedValue({ error: submitError }),
    };

    const stripe = {
        confirmPayment: vi.fn().mockResolvedValue({ error: confirmError }),
    };

    return { elements, stripe };
}

describe('processPaymentSubmission', () => {
    it('does not call confirmPayment when elements.submit returns an error', async () => {
        const submitError = { message: 'Your card number is incomplete.' };
        const { elements, stripe } = createMocks({ submitError });

        const result = await processPaymentSubmission({
            elements,
            stripe,
            clientSecret,
            returnUrl,
        });

        expect(elements.submit).toHaveBeenCalledOnce();
        expect(stripe.confirmPayment).not.toHaveBeenCalled();
        expect(result).toEqual({ stage: 'submit', error: submitError });
    });

    it('calls confirmPayment after a successful elements.submit', async () => {
        const { elements, stripe } = createMocks();

        const result = await processPaymentSubmission({
            elements,
            stripe,
            clientSecret,
            returnUrl,
        });

        expect(elements.submit).toHaveBeenCalledBefore(stripe.confirmPayment);
        expect(stripe.confirmPayment).toHaveBeenCalledWith({
            elements,
            clientSecret,
            confirmParams: {
                return_url: returnUrl,
            },
        });
        expect(result).toEqual({ stage: 'success' });
    });

    it('returns confirm stage errors from confirmPayment', async () => {
        const confirmError = { message: 'Your card was declined.' };
        const { elements, stripe } = createMocks({ confirmError });

        const result = await processPaymentSubmission({
            elements,
            stripe,
            clientSecret,
            returnUrl,
        });

        expect(elements.submit).toHaveBeenCalledOnce();
        expect(stripe.confirmPayment).toHaveBeenCalledOnce();
        expect(result).toEqual({ stage: 'confirm', error: confirmError });
    });
});
