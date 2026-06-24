import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import {
    explainWhatsAppPhoneValidation,
    initialPhoneNumber,
    iso2FromCountryCode,
    isWhatsAppPhoneValid,
    partsFromIti,
    resolveItiFromInput,
    whatsappPhoneCountryLabel,
} from './helpers';

const WHATSAPP_PHONE_INPUT_ID = 'whatsapp_phone_input';

function shouldLogWhatsAppPhoneState() {
    return import.meta.env?.DEV
        || new URLSearchParams(globalThis.location?.search ?? '').has('debug_whatsapp');
}

function logWhatsAppPhoneInit(count, reason) {
    if (shouldLogWhatsAppPhoneState()) {
        console.info(`[whatsapp-phone] intl-tel-input init #${count} (${reason})`);
    }
}

function logWhatsAppPhoneState(state) {
    if (! shouldLogWhatsAppPhoneState()) {
        return;
    }

    console.info('[whatsapp-phone] state', state);
}

export function createWhatsAppPhoneField(config = {}) {
    return {
        whatsappCountryCode: config.whatsappCountryCode ?? '+44',
        whatsappPhone: config.whatsappPhone ?? '',
        whatsappE164: initialPhoneNumber(config.whatsappCountryCode, config.whatsappPhone),
        whatsappPhoneValid: false,
        whatsappPhoneCountryLabel: '',
        _whatsappItiInput: null,
        _whatsappItiInitCount: 0,
        _whatsappEnsureTimer: null,
        _whatsappUsesSeparateDialCode: true,

        resolveWhatsAppPhoneInput() {
            return this.$refs?.whatsappPhoneInput
                ?? globalThis.document?.getElementById(WHATSAPP_PHONE_INPUT_ID)
                ?? null;
        },

        resolveItiInstance() {
            const input = this._whatsappItiInput ?? this.resolveWhatsAppPhoneInput();

            return resolveItiFromInput(input);
        },

        initWhatsAppPhoneInput() {
            const input = this.resolveWhatsAppPhoneInput();

            if (! input) {
                return false;
            }

            const existingIti = resolveItiFromInput(input);

            if (existingIti && this._whatsappItiInput === input) {
                return true;
            }

            if (existingIti) {
                this.destroyWhatsAppPhoneInput();
            }

            const initialCountry = iso2FromCountryCode(this.whatsappCountryCode);
            const presetNumber = initialPhoneNumber(this.whatsappCountryCode, this.whatsappPhone);
            const existingValue = input.value?.trim() ?? '';

            try {
                const iti = intlTelInput(input, {
                    initialCountry,
                    separateDialCode: true,
                    countrySearch: true,
                    matchDropdownWidth: true,
                    strictMode: true,
                    formatAsYouType: true,
                    containerClass: 'whatsapp-phone-iti',
                    uiTranslations: {
                        searchPlaceholder: 'Search country',
                    },
                });

                this._whatsappItiInput = input;
                this._whatsappItiInitCount += 1;
                this._whatsappUsesSeparateDialCode = true;
                logWhatsAppPhoneInit(this._whatsappItiInitCount, 'create');

                if (existingValue !== '') {
                    iti.setNumber(existingValue);
                } else if (presetNumber !== '') {
                    iti.setNumber(presetNumber);
                }

                const sync = () => this.syncWhatsAppPhoneFromInput();

                input.addEventListener('countrychange', sync);
                input.addEventListener('input', sync);
                input.addEventListener('blur', sync);

                sync();

                return true;
            } catch (error) {
                console.error('WhatsApp phone input failed to initialize.', error);
                this._whatsappItiInput = null;

                return false;
            }
        },

        destroyWhatsAppPhoneInput() {
            if (this._whatsappEnsureTimer !== null) {
                globalThis.clearTimeout(this._whatsappEnsureTimer);
                this._whatsappEnsureTimer = null;
            }

            const input = this._whatsappItiInput ?? this.resolveWhatsAppPhoneInput();
            const iti = resolveItiFromInput(input);

            if (! iti) {
                return;
            }

            iti.destroy();
            this._whatsappItiInput = null;
            this.whatsappPhoneValid = false;
            this.whatsappPhoneCountryLabel = '';
        },

        syncWhatsAppPhoneFromInput() {
            const input = this._whatsappItiInput ?? this.resolveWhatsAppPhoneInput();
            const iti = resolveItiFromInput(input);

            const parts = partsFromIti(iti, {
                separateDialCode: this._whatsappUsesSeparateDialCode,
                input,
            });

            this.whatsappCountryCode = parts.countryCode;
            this.whatsappPhone = parts.national;
            this.whatsappE164 = parts.e164;
            this.whatsappPhoneValid = isWhatsAppPhoneValid(parts);
            this.whatsappPhoneCountryLabel = whatsappPhoneCountryLabel(parts);

            const validationReason = explainWhatsAppPhoneValidation(parts);

            if (shouldLogWhatsAppPhoneState()) {
                console.log({
                    whatsappPhone: this.whatsappPhone,
                    whatsappPhoneValid: this.whatsappPhoneValid,
                    selectedCountry: parts.iso2,
                    e164: this.whatsappE164,
                    whatsappOtpSending: this.whatsappOtpSending ?? false,
                    validationReason,
                    libPhoneValid: parts.valid,
                    rawInput: parts.rawInput ?? '',
                    itiReady: Boolean(iti),
                    itiStoredOnInput: Boolean(input?.iti),
                });

                logWhatsAppPhoneState({
                    whatsappPhone: this.whatsappPhone,
                    whatsappPhoneValid: this.whatsappPhoneValid,
                    selectedCountry: parts.iso2,
                    nationalNumber: parts.national,
                    e164: parts.e164,
                    dialCode: parts.dialCode,
                    validationReason,
                    libPhoneValid: parts.valid,
                    itiReady: Boolean(iti),
                });
            }
        },

        bindWhatsAppPhoneWatchers() {
            this.$watch('whatsapp', (enabled, wasEnabled) => {
                if (! enabled) {
                    this.destroyWhatsAppPhoneInput();
                    this.resetWhatsAppVerification?.();

                    return;
                }

                if (! wasEnabled) {
                    this.ensureWhatsAppPhoneInputReady();
                }
            });

            this.$watch('whatsappOtpStep', (step) => {
                if (this.whatsapp && step === 'phone' && ! this.resolveItiInstance()) {
                    this.ensureWhatsAppPhoneInputReady();
                }
            });
        },

        ensureWhatsAppPhoneInputReady(attempt = 0) {
            const input = this.resolveWhatsAppPhoneInput();

            if (resolveItiFromInput(input) && this._whatsappItiInput === input) {
                return;
            }

            if (this._whatsappEnsureTimer !== null) {
                return;
            }

            const delay = attempt === 0 ? 0 : 50 * attempt;

            this._whatsappEnsureTimer = globalThis.setTimeout(() => {
                this._whatsappEnsureTimer = null;

                this.$nextTick(() => {
                    const initialized = this.initWhatsAppPhoneInput();

                    if (! initialized && attempt < 12) {
                        this.ensureWhatsAppPhoneInputReady(attempt + 1);
                    }
                });
            }, delay);
        },
    };
}
