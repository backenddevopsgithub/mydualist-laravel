import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import {
    initialPhoneNumber,
    iso2FromCountryCode,
    isWhatsAppPhoneValid,
    partsFromIti,
    whatsappPhoneCountryLabel,
} from './helpers';

export function createWhatsAppPhoneField(config = {}) {
    return {
        whatsappCountryCode: config.whatsappCountryCode ?? '+44',
        whatsappPhone: config.whatsappPhone ?? '',
        whatsappE164: initialPhoneNumber(config.whatsappCountryCode, config.whatsappPhone),
        whatsappPhoneValid: false,
        whatsappPhoneCountryLabel: '',
        _whatsappIti: null,
        _whatsappItiInput: null,

        initWhatsAppPhoneInput() {
            const input = this.$refs?.whatsappPhoneInput;

            if (! input) {
                return false;
            }

            if (this._whatsappIti && this._whatsappItiInput === input) {
                this.syncWhatsAppPhoneFromInput();

                return true;
            }

            if (this._whatsappIti) {
                this.destroyWhatsAppPhoneInput();
            }

            const initialCountry = iso2FromCountryCode(this.whatsappCountryCode);
            const presetNumber = initialPhoneNumber(this.whatsappCountryCode, this.whatsappPhone);

            try {
                this._whatsappIti = intlTelInput(input, {
                    initialCountry,
                    separateDialCode: false,
                    countrySearch: true,
                    matchDropdownWidth: true,
                    strictMode: false,
                    formatAsYouType: true,
                    containerClass: 'whatsapp-phone-iti',
                    uiTranslations: {
                        searchPlaceholder: 'Search country',
                    },
                });
                this._whatsappItiInput = input;

                if (presetNumber !== '') {
                    this._whatsappIti.setNumber(presetNumber);
                }

                const sync = () => this.syncWhatsAppPhoneFromInput();

                input.addEventListener('countrychange', sync);
                input.addEventListener('input', sync);
                input.addEventListener('blur', sync);

                sync();

                return true;
            } catch (error) {
                console.error('WhatsApp phone input failed to initialize.', error);
                this._whatsappIti = null;
                this._whatsappItiInput = null;

                return false;
            }
        },

        destroyWhatsAppPhoneInput() {
            if (! this._whatsappIti) {
                return;
            }

            this._whatsappIti.destroy();
            this._whatsappIti = null;
            this._whatsappItiInput = null;
            this.whatsappPhoneValid = false;
            this.whatsappPhoneCountryLabel = '';
        },

        syncWhatsAppPhoneFromInput() {
            const parts = partsFromIti(this._whatsappIti);

            this.whatsappCountryCode = parts.countryCode;
            this.whatsappPhone = parts.national;
            this.whatsappE164 = parts.e164;
            this.whatsappPhoneValid = isWhatsAppPhoneValid(parts);
            this.whatsappPhoneCountryLabel = whatsappPhoneCountryLabel(parts);
        },

        bindWhatsAppPhoneWatchers() {
            this.$watch('whatsapp', (enabled) => {
                if (! enabled) {
                    this.destroyWhatsAppPhoneInput();
                    this.resetWhatsAppVerification();

                    return;
                }

                this.ensureWhatsAppPhoneInputReady();
            });

            this.$watch('whatsappOtpStep', (step) => {
                if (this.whatsapp && step === 'phone') {
                    this.ensureWhatsAppPhoneInputReady();
                }
            });

            if (this.whatsapp) {
                this.ensureWhatsAppPhoneInputReady();
            }
        },

        ensureWhatsAppPhoneInputReady(attempt = 0) {
            this.$nextTick(() => {
                const initialized = this.initWhatsAppPhoneInput();

                if (! initialized && attempt < 8) {
                    window.setTimeout(
                        () => this.ensureWhatsAppPhoneInputReady(attempt + 1),
                        50 * (attempt + 1),
                    );
                }
            });
        },
    };
}
