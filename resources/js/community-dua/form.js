import { createWhatsAppPhoneField } from '../whatsapp-phone/intl-tel-input-field';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
}

export function createCommunityDuaForm(config = {}) {
    return {
        ...createWhatsAppPhoneField({
            whatsappCountryCode: config.whatsappCountryCode ?? '+44',
            whatsappPhone: config.whatsappPhone ?? '',
        }),
        whatsapp: config.whatsapp ?? false,
        whatsappVerified: config.whatsappVerified ?? false,
        whatsappVerificationToken: config.whatsappVerificationToken ?? '',
        whatsappOtpStep: config.whatsappOtpStep ?? 'phone',
        whatsappOtp: '',
        whatsappOtpError: null,
        whatsappOtpMessage: null,
        whatsappOtpSending: false,
        whatsappOtpVerifying: false,
        whatsappOtpResent: false,
        submitting: false,

        get canSubmit() {
            return ! this.whatsapp || this.whatsappVerified;
        },

        submitForm(event) {
            if (! this.canSubmit) {
                event.preventDefault();
                this.whatsappOtpError = 'Error: Please verify phone number.';

                return;
            }

            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },

        resetWhatsAppVerification() {
            this.whatsappVerified = false;
            this.whatsappVerificationToken = '';
            this.whatsappOtpStep = 'phone';
            this.whatsappOtp = '';
            this.whatsappOtpError = null;
            this.whatsappOtpMessage = null;
            this.whatsappOtpResent = false;
        },

        async sendWhatsAppOtp() {
            this.syncWhatsAppPhoneFromInput();
            this.whatsappOtpError = null;
            this.whatsappOtpMessage = null;

            if (! this.whatsappPhoneValid) {
                this.whatsappOtpError = 'Error: Invalid Phone Number';

                return;
            }

            this.whatsappOtpSending = true;

            try {
                const response = await fetch('/api/v1/public/submissions/otp/send', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        whatsapp_country_code: this.whatsappCountryCode,
                        whatsapp_phone: this.whatsappPhone,
                    }),
                });

                const payload = await response.json();

                if (! response.ok) {
                    const message = payload?.errors?.whatsapp_phone?.[0]
                        ?? payload?.message
                        ?? 'Unable to send OTP.';

                    throw new Error(message);
                }

                this.whatsappOtpStep = 'otp';
                this.whatsappOtpMessage = 'Check WhatsApp for your OTP.';
            } catch (error) {
                this.whatsappOtpError = error instanceof Error ? error.message : 'Unable to send OTP.';
            } finally {
                this.whatsappOtpSending = false;
            }
        },

        async verifyWhatsAppOtp() {
            this.syncWhatsAppPhoneFromInput();
            this.whatsappOtpError = null;
            this.whatsappOtpMessage = null;
            this.whatsappOtpVerifying = true;

            try {
                const response = await fetch('/api/v1/public/submissions/otp/verify', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken(),
                    },
                    body: JSON.stringify({
                        whatsapp_country_code: this.whatsappCountryCode,
                        whatsapp_phone: this.whatsappPhone,
                        otp: this.whatsappOtp,
                    }),
                });

                const payload = await response.json();

                if (! response.ok) {
                    const message = payload?.errors?.otp?.[0]
                        ?? payload?.message
                        ?? 'Invalid Authentication Code';

                    throw new Error(message);
                }

                this.whatsappVerified = true;
                this.whatsappVerificationToken = payload?.data?.verification_token ?? '';
                this.whatsappOtpMessage = 'WhatsApp verification completed!';
            } catch (error) {
                this.whatsappOtpError = error instanceof Error ? error.message : 'Invalid Authentication Code';
            } finally {
                this.whatsappOtpVerifying = false;
            }
        },

        async resendWhatsAppOtp() {
            if (this.whatsappOtpResent) {
                return;
            }

            await this.sendWhatsAppOtp();
            this.whatsappOtpResent = true;
            this.whatsappOtpMessage = 'OTP resent!';
        },

        init() {
            this.bindWhatsAppPhoneWatchers();

            if (this.whatsapp && this.whatsappOtpStep === 'phone' && ! this.whatsappVerified) {
                this.ensureWhatsAppPhoneInputReady();
            }
        },
    };
}

export function registerCommunityDuaForm(Alpine) {
    Alpine.data('communityDuaForm', (config = {}) => createCommunityDuaForm(config));
}
