import { describe, expect, it } from 'vitest';
import {
    buildCountryCode,
    buildE164FromInput,
    explainWhatsAppPhoneValidation,
    initialPhoneNumber,
    iso2FromCountryCode,
    isWhatsAppPhoneValid,
    partsFromIti,
    resolveItiFromInput,
    selectedCountryFromIti,
    splitE164Parts,
    whatsappPhoneCountryLabel,
} from './helpers';

function createV29ItiMock({
    dialCode = '92',
    iso2 = 'pk',
    name = 'Pakistan',
    rawInput = '',
    e164 = '',
    valid = false,
} = {}) {
    const input = { value: rawInput };

    return {
        telInput: input,
        getSelectedCountry: () => ({
            dialCode,
            iso2,
            name,
        }),
        getNumber: () => e164,
        isValidNumber: () => valid,
        isValidNumberPrecise: () => valid,
    };
}

describe('whatsapp phone helpers', () => {
    it('builds country codes with a leading plus', () => {
        expect(buildCountryCode('92')).toBe('+92');
        expect(buildCountryCode('+44')).toBe('+44');
    });

    it('splits e164 numbers into country code and national parts', () => {
        expect(splitE164Parts('+923001234567', '92')).toEqual({
            countryCode: '+92',
            national: '3001234567',
        });

        expect(splitE164Parts('+447700900123', '44')).toEqual({
            countryCode: '+44',
            national: '7700900123',
        });
    });

    it('maps stored country codes to iso2 values for intl-tel-input', () => {
        expect(iso2FromCountryCode('+44')).toBe('gb');
        expect(iso2FromCountryCode('+92')).toBe('pk');
    });

    it('rebuilds the initial e164 value from persisted fields', () => {
        expect(initialPhoneNumber('+92', '3001234567')).toBe('+923001234567');
        expect(initialPhoneNumber('+44', '7700900123')).toBe('+447700900123');
    });

    it('reads country data from intl-tel-input v29 getSelectedCountry', () => {
        const iti = createV29ItiMock();

        expect(selectedCountryFromIti(iti)).toEqual({
            dialCode: '92',
            iso2: 'pk',
            name: 'Pakistan',
        });
    });

    it('derives backend fields from an intl-tel-input v29 instance', () => {
        const iti = createV29ItiMock({
            rawInput: '3001234567',
            e164: '+923001234567',
            valid: true,
        });

        expect(partsFromIti(iti)).toEqual({
            countryCode: '+92',
            national: '3001234567',
            e164: '+923001234567',
            valid: true,
            countryName: 'Pakistan',
            iso2: 'pk',
            dialCode: '92',
            rawInput: '3001234567',
        });
    });

    it('falls back to raw input digits when getNumber returns an empty string', () => {
        const iti = createV29ItiMock({
            rawInput: '3142266843',
            e164: '',
            valid: false,
        });

        expect(partsFromIti(iti)).toMatchObject({
            countryCode: '+92',
            national: '3142266843',
            e164: '+923142266843',
            iso2: 'pk',
        });

        expect(isWhatsAppPhoneValid(partsFromIti(iti))).toBe(true);
    });

    it('reads the stored input element when intl-tel-input v29 does not expose telInput', () => {
        const input = { value: '3142266843' };
        const iti = {
            getSelectedCountry: () => ({
                dialCode: '92',
                iso2: 'pk',
                name: 'Pakistan',
            }),
            getNumber: () => '',
            isValidNumber: () => false,
        };

        const parts = partsFromIti(iti, { separateDialCode: true, input });

        expect(parts).toMatchObject({
            national: '3142266843',
            e164: '+923142266843',
            iso2: 'pk',
            rawInput: '3142266843',
        });
        expect(explainWhatsAppPhoneValidation(parts)).toBe('pk-valid');
        expect(isWhatsAppPhoneValid(parts)).toBe(true);
    });

    it('resolves the iti instance from the input element reference', () => {
        const iti = { id: 'real-iti' };
        const input = { iti };

        expect(resolveItiFromInput(input)).toBe(iti);
    });

    it('does not call intl-tel-input methods through an alpine proxy', () => {
        class ItiLike {
            #country = {
                dialCode: '92',
                iso2: 'pk',
                name: 'Pakistan',
            };

            getSelectedCountry() {
                return this.#country;
            }

            getNumber() {
                return '+923142266843';
            }

            isValidNumber() {
                return true;
            }
        }

        const iti = new ItiLike();
        const proxiedIti = new Proxy(iti, {});
        const input = { value: '3142266843', iti };

        expect(() => selectedCountryFromIti(proxiedIti)).toThrow(/private/i);
        expect(partsFromIti(proxiedIti, { separateDialCode: true, input })).toMatchObject({
            national: '3142266843',
            iso2: 'pk',
            valid: true,
        });
    });

    it('builds e164 from separate dial code input values', () => {
        expect(buildE164FromInput('92', '3142266843', true)).toBe('+923142266843');
    });

    it('builds an accessible country label for screen readers', () => {
        expect(whatsappPhoneCountryLabel({
            countryName: 'Pakistan',
            countryCode: '+92',
            iso2: 'pk',
        })).toBe('Pakistan (+92)');
    });

    it('accepts plausible national numbers when libphonenumber has not marked them valid yet', () => {
        expect(isWhatsAppPhoneValid({
            valid: false,
            national: '7700900123',
            dialCode: '44',
            countryCode: '+44',
            iso2: 'gb',
        })).toBe(true);

        expect(isWhatsAppPhoneValid({
            valid: false,
            national: '3142266843',
            dialCode: '92',
            countryCode: '+92',
            iso2: 'pk',
        })).toBe(true);

        expect(isWhatsAppPhoneValid({
            valid: false,
            national: '12',
            dialCode: '44',
            countryCode: '+44',
            iso2: 'gb',
        })).toBe(false);
    });
});
