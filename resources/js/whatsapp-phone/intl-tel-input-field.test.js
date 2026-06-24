import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock('intl-tel-input/intlTelInputWithUtils', () => ({
    default: vi.fn((input) => {
        const iti = {
            destroy: vi.fn(),
            setNumber: vi.fn(),
            getNumber: () => '',
            getSelectedCountry: () => ({ dialCode: '92', name: 'Pakistan', iso2: 'pk' }),
            isValidNumberPrecise: () => false,
            isValidNumber: () => false,
        };

        input.iti = iti;

        return iti;
    }),
}));

import intlTelInput from 'intl-tel-input/intlTelInputWithUtils';
import { createWhatsAppPhoneField } from './intl-tel-input-field';

function createInput() {
    return {
        value: '',
        addEventListener: vi.fn(),
    };
}

function createField(overrides = {}) {
    const input = createInput();
    const field = createWhatsAppPhoneField({
        whatsappCountryCode: '+92',
        whatsappPhone: '',
    });

    return {
        $refs: { whatsappPhoneInput: input },
        $nextTick: (callback) => callback(),
        $watch: vi.fn(),
        ...field,
        ...overrides,
    };
}

describe('whatsapp phone field init guards', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.mocked(intlTelInput).mockClear();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('initializes intl-tel-input only once for the same input element', () => {
        const field = createField();

        expect(field.initWhatsAppPhoneInput()).toBe(true);
        expect(field.initWhatsAppPhoneInput()).toBe(true);
        expect(intlTelInput).toHaveBeenCalledTimes(1);
    });

    it('stores the iti instance on the input element instead of alpine state', () => {
        const field = createField();

        field.initWhatsAppPhoneInput();

        expect(field.resolveItiInstance()).toBe(field.$refs.whatsappPhoneInput.iti);
        expect(field._whatsappIti).toBeUndefined();
    });

    it('deduplicates ensureWhatsAppPhoneInputReady scheduling', () => {
        const field = createField();

        field.ensureWhatsAppPhoneInputReady();
        field.ensureWhatsAppPhoneInputReady();
        field.ensureWhatsAppPhoneInputReady();

        vi.runAllTimers();

        expect(intlTelInput).toHaveBeenCalledTimes(1);
    });

    it('does not reschedule ensure when the input is already ready', () => {
        const field = createField();

        field.initWhatsAppPhoneInput();
        field.ensureWhatsAppPhoneInputReady();

        vi.runAllTimers();

        expect(intlTelInput).toHaveBeenCalledTimes(1);
    });
});
