/**
 * @param {{
 *   elements: { submit: () => Promise<{ error?: { message?: string } }> },
 *   stripe: { confirmPayment: (options: object) => Promise<{ error?: { message?: string } }> },
 *   clientSecret: string,
 *   returnUrl: string,
 * }} options
 * @returns {Promise<{ stage: 'submit' | 'confirm' | 'success', error?: { message?: string } }>}
 */
export async function processPaymentSubmission({
    elements,
    stripe,
    clientSecret,
    returnUrl,
}) {
    const { error: submitError } = await elements.submit();

    if (submitError) {
        return { stage: 'submit', error: submitError };
    }

    const { error: confirmError } = await stripe.confirmPayment({
        elements,
        clientSecret,
        confirmParams: {
            return_url: returnUrl,
        },
    });

    if (confirmError) {
        return { stage: 'confirm', error: confirmError };
    }

    return { stage: 'success' };
}
