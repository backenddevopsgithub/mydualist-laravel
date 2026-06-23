import allCountries from 'intl-tel-input/data';

const preferredIso2ByDialCode = {
    44: 'gb',
    1: 'us',
};

export function normalizeDigits(value) {
    return String(value ?? '').replace(/\D/g, '');
}

export function buildCountryCode(dialCode) {
    const digits = normalizeDigits(dialCode);

    return digits === '' ? '' : `+${digits}`;
}

export function splitE164Parts(e164, dialCode) {
    const digits = normalizeDigits(e164);
    const dialDigits = normalizeDigits(dialCode);

    if (dialDigits === '' || digits === '') {
        return {
            countryCode: buildCountryCode(dialCode),
            national: '',
        };
    }

    if (! digits.startsWith(dialDigits)) {
        return {
            countryCode: buildCountryCode(dialCode),
            national: '',
        };
    }

    return {
        countryCode: buildCountryCode(dialDigits),
        national: digits.slice(dialDigits.length).replace(/^0+/, ''),
    };
}

export function iso2FromCountryCode(countryCode, fallback = 'gb') {
    const dialDigits = normalizeDigits(countryCode);

    if (dialDigits === '') {
        return fallback;
    }

    if (preferredIso2ByDialCode[dialDigits]) {
        return preferredIso2ByDialCode[dialDigits];
    }

    const match = allCountries.find((country) => country.dialCode === dialDigits);

    return match?.iso2 ?? fallback;
}

export function initialPhoneNumber(countryCode, nationalPhone) {
    const dialDigits = normalizeDigits(countryCode);
    const nationalDigits = normalizeDigits(nationalPhone);

    if (dialDigits === '' || nationalDigits === '') {
        return '';
    }

    return `+${dialDigits}${nationalDigits}`;
}

export function partsFromIti(iti) {
    if (! iti) {
        return {
            countryCode: '',
            national: '',
            e164: '',
            valid: false,
            countryName: '',
            iso2: '',
        };
    }

    const data = iti.getSelectedCountryData();
    const e164 = iti.getNumber() ?? '';
    const { countryCode, national } = splitE164Parts(e164, data.dialCode);
    const valid = iti.isValidNumber?.() ?? iti.isValidNumberPrecise?.() ?? false;

    return {
        countryCode,
        national,
        e164,
        valid: Boolean(valid),
        countryName: data.name ?? '',
        iso2: data.iso2 ?? '',
    };
}

export function whatsappPhoneCountryLabel(parts) {
    if (! parts.countryName) {
        return '';
    }

    const dial = parts.countryCode || buildCountryCode(parts.iso2);

    return `${parts.countryName} (${dial})`;
}
