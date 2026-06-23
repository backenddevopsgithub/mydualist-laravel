import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import {
    initialPhoneNumber,
    iso2FromCountryCode,
    partsFromIti,
    whatsappPhoneCountryLabel,
} from './helpers';

export function createWhatsAppPhoneField(config = {}) {
    let itiInstance = null;

    return {
        whatsappCountryCode: config.whatsappCountryCode ?? '+44',
        whatsappPhone: config.whatsappPhone ?? '',
        whatsappE164: initialPhoneNumber(config.whatsappCountryCode, config.whatsappPhone),
        whatsappPhoneValid: false,
        whatsappPhoneCountryLabel: '',

        initWhatsAppPhoneInput() {
            const input = this.$refs.whatsappPhoneInput;

            if (! input || itiInstance) {
                if (itiInstance) {
                    this.syncWhatsAppPhoneFromInput();
                }

                return;
            }

            const initialCountry = iso2FromCountryCode(this.whatsappCountryCode);
            const presetNumber = initialPhoneNumber(this.whatsappCountryCode, this.whatsappPhone);

            itiInstance = intlTelInput(input, {
                initialCountry,
                separateDialCode: false,
                countrySearch: true,
                matchDropdownWidth: true,
                strictMode: true,
                formatAsYouType: true,
                containerClass: 'whatsapp-phone-iti',
                uiTranslations: {
                    searchPlaceholder: 'Search country',
                },
            });

            if (presetNumber !== '') {
                itiInstance.setNumber(presetNumber);
            }

            const sync = () => this.syncWhatsAppPhoneFromInput();

            input.addEventListener('countrychange', sync);
            input.addEventListener('input', sync);
            input.addEventListener('blur', sync);

            sync();
        },

        destroyWhatsAppPhoneInput() {
            if (! itiInstance) {
                return;
            }

            itiInstance.destroy();
            itiInstance = null;
            this.whatsappPhoneValid = false;
            this.whatsappPhoneCountryLabel = '';
        },

        syncWhatsAppPhoneFromInput() {
            const parts = partsFromIti(itiInstance);

            this.whatsappCountryCode = parts.countryCode;
            this.whatsappPhone = parts.national;
            this.whatsappE164 = parts.e164;
            this.whatsappPhoneValid = parts.valid;
            this.whatsappPhoneCountryLabel = whatsappPhoneCountryLabel(parts);
        },

        bindWhatsAppPhoneWatchers() {
            this.$watch('whatsapp', (enabled) => {
                if (! enabled) {
                    this.destroyWhatsAppPhoneInput();
                    this.resetWhatsAppVerification();

                    return;
                }

                this.scheduleWhatsAppPhoneInit();
            });

            if (this.whatsapp) {
                this.scheduleWhatsAppPhoneInit();
            }
        },

        scheduleWhatsAppPhoneInit() {
            this.$nextTick(() => {
                this.initWhatsAppPhoneInput();

                if (! this.whatsappPhoneValid) {
                    window.setTimeout(() => this.initWhatsAppPhoneInput(), 50);
                }
            });
        },
    };
}
