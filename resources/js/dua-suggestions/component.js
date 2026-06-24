import {
    appendSuggestionText,
    mapSuggestionsResponse,
    replaceNamePlaceholder,
    resolveActiveDuaIndex,
    resolveActiveDuaIndexAfterRemoval,
    trackSuggestionId,
    visibleSuggestionCount,
} from './helpers';
import { createWhatsAppPhoneField } from '../whatsapp-phone/intl-tel-input-field';

export const SUGGESTION_SECTIONS = [
    { key: 'general', label: 'General' },
    { key: 'quran', label: 'Quran' },
    { key: 'sunnah', label: 'Sunnah' },
];

export function createSuggestionMixin(slug, initialSelectedIds = []) {
    return {
        suggestionSlug: slug,
        suggestionsLoading: false,
        suggestionsLoadError: null,
        suggestionsLoaded: false,
        suggestions: {
            general: [],
            quran: [],
            sunnah: [],
        },
        expandedSuggestionSections: {
            general: false,
            quran: false,
            sunnah: false,
        },
        selectedSuggestionIds: [...initialSelectedIds],
        suggestionVisibleLimit: 4,
        suggestionSections: SUGGESTION_SECTIONS,

        async loadSuggestions() {
            if (this.suggestionsLoaded || this.suggestionsLoading) {
                return;
            }

            this.suggestionsLoading = true;
            this.suggestionsLoadError = null;

            const url = `/api/v1/public/lists/${this.suggestionSlug}/suggestions`;

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) {
                    throw new Error('Unable to load suggestions.');
                }

                const payload = await response.json();
                const mapped = mapSuggestionsResponse(payload);

                this.suggestions.general = mapped.general;
                this.suggestions.quran = mapped.quran;
                this.suggestions.sunnah = mapped.sunnah;
                this.suggestionsLoaded = true;
            } catch (error) {
                this.suggestionsLoadError = error instanceof Error ? error.message : 'Unable to load suggestions.';
            } finally {
                this.suggestionsLoading = false;
            }
        },

        hasSuggestionSection(section) {
            return (this.suggestions[section] ?? []).length > 0;
        },

        visibleSuggestions(section) {
            const items = this.suggestions[section] ?? [];
            const count = visibleSuggestionCount(
                items.length,
                this.expandedSuggestionSections[section],
                this.suggestionVisibleLimit,
            );

            return items.slice(0, count);
        },

        hasMoreSuggestions(section) {
            const items = this.suggestions[section] ?? [];

            return ! this.expandedSuggestionSections[section] && items.length > this.suggestionVisibleLimit;
        },

        showMoreSuggestions(section) {
            this.expandedSuggestionSections[section] = true;
        },

        selectSuggestion(suggestion) {
            const index = resolveActiveDuaIndex(this.activeDuaIndex, this.duas.length);
            this.activeDuaIndex = index;

            const text = replaceNamePlaceholder(suggestion.content, this.firstName);
            this.duas[index] = appendSuggestionText(this.duas[index] ?? '', text);
            this.selectedSuggestionIds = trackSuggestionId(this.selectedSuggestionIds, suggestion.id);

            this.$nextTick(() => {
                this.focusDuaField(index);
            });
        },

        setActiveDuaIndex(index) {
            this.activeDuaIndex = resolveActiveDuaIndex(index, this.duas.length);
        },

        focusDuaField(index) {
            const field = document.getElementById(`dua-field-${index}`);

            field?.querySelector('textarea')?.focus();
        },

        resetSelectedSuggestionIds() {
            this.selectedSuggestionIds = [];
        },
    };
}

export function createPublicSubmissionForm(config) {
    return {
        ...createWhatsAppPhoneField({
            whatsappCountryCode: config.whatsappCountryCode ?? '+44',
            whatsappPhone: config.whatsappPhone ?? '',
        }),
        ...createSuggestionMixin(config.slug, config.selectedSuggestionIds ?? []),
        step: config.step ?? 'info',
        showGuide: false,
        maxDuas: 35,
        duas: config.duas ?? [''],
        activeDuaIndex: config.activeDuaIndex ?? 0,
        gender: config.gender ?? '',
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
        terms: config.terms ?? false,
        firstName: config.firstName ?? '',
        lastName: config.lastName ?? '',
        email: config.email ?? '',
        submissionBatchKey: config.submissionBatchKey ?? (globalThis.crypto?.randomUUID?.() ?? ''),
        submitting: false,

        submitForm(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },

        get canContinue() {
            return this.firstName.trim() !== ''
                && this.lastName.trim() !== ''
                && this.email.trim() !== ''
                && this.gender !== ''
                && this.terms
                && (! this.whatsapp || this.whatsappVerified);
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            ?? document.querySelector('input[name="_token"]')?.value
                            ?? '',
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            ?? document.querySelector('input[name="_token"]')?.value
                            ?? '',
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

            if (this.step === 'duas') {
                this.loadSuggestions();
            }
        },

        addDua() {
            if (this.duas.length < this.maxDuas) {
                const index = this.duas.length;
                this.duas.push('');
                this.activeDuaIndex = index;

                this.$nextTick(() => {
                    const field = document.getElementById(`dua-field-${index}`);
                    field?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    field?.querySelector('textarea')?.focus();
                });
            }
        },

        removeDua(index) {
            if (this.duas.length > 1) {
                this.duas.splice(index, 1);
                this.activeDuaIndex = resolveActiveDuaIndexAfterRemoval(
                    index,
                    this.activeDuaIndex,
                    this.duas.length,
                );
            }
        },

        openDuas() {
            this.showGuide = true;
        },

        async acceptGuide() {
            this.showGuide = false;
            this.step = 'duas';
            await this.loadSuggestions();
            this.activeDuaIndex = 0;
            this.$nextTick(() => this.focusDuaField(0));
        },
    };
}

export function registerDuaSuggestionsComponent(Alpine) {
    Alpine.data('publicSubmissionForm', (config = {}) => createPublicSubmissionForm(config));
}
