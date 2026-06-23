import { describe, expect, it } from 'vitest';
import {
    buildCountryCode,
    initialPhoneNumber,
    iso2FromCountryCode,
    isWhatsAppPhoneValid,
    partsFromIti,
    splitE164Parts,
    whatsappPhoneCountryLabel,
} from './helpers';

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

    it('derives backend fields from an intl-tel-input instance', () => {
        const iti = {
            getSelectedCountryData: () => ({
                dialCode: '92',
                name: 'Pakistan',
                iso2: 'pk',
            }),
            getNumber: () => '+923001234567',
            isValidNumberPrecise: () => true,
        };

        expect(partsFromIti(iti)).toEqual({
            countryCode: '+92',
            national: '3001234567',
            e164: '+923001234567',
            valid: true,
            countryName: 'Pakistan',
            iso2: 'pk',
            dialCode: '92',
        });
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
        })).toBe(true);

        expect(isWhatsAppPhoneValid({
            valid: false,
            national: '12',
            dialCode: '44',
            countryCode: '+44',
        })).toBe(false);
    });
});
