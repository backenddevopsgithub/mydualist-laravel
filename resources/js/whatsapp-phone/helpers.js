import allCountries from 'intl-tel-input/data';

const preferredIso2ByDialCode = {
    44: 'gb',
    1: 'us',
    92: 'pk',
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

export function resolveItiFromInput(input) {
    if (! input?.iti) {
        return null;
    }

    return input.iti;
}

export function selectedCountryFromIti(iti) {
    if (! iti) {
        return null;
    }

    if (typeof iti.getSelectedCountry === 'function') {
        return iti.getSelectedCountry();
    }

    if (typeof iti.getSelectedCountryData === 'function') {
        return iti.getSelectedCountryData();
    }

    return null;
}

export function numberFromIti(iti) {
    if (! iti || typeof iti.getNumber !== 'function') {
        return '';
    }

    return iti.getNumber() ?? '';
}

export function isItiNumberValid(iti) {
    if (! iti) {
        return false;
    }

    const precise = iti.isValidNumberPrecise?.();
    const standard = iti.isValidNumber?.();

    return Boolean(precise ?? standard ?? false);
}

export function inputElementFromIti(iti, inputOverride = null) {
    if (inputOverride) {
        return inputOverride;
    }

    if (! iti) {
        return null;
    }

    return iti.telInput ?? iti.ui?.telInputEl ?? null;
}

export function buildE164FromInput(dialCode, rawInput, separateDialCode = true) {
    const dialDigits = normalizeDigits(dialCode);
    const inputDigits = normalizeDigits(rawInput);

    if (dialDigits === '' || inputDigits === '') {
        return '';
    }

    if (separateDialCode) {
        const national = inputDigits.replace(/^0+/, '');

        return national === '' ? '' : `+${dialDigits}${national}`;
    }

    if (inputDigits.startsWith(dialDigits)) {
        return `+${inputDigits}`;
    }

    return `+${dialDigits}${inputDigits.replace(/^0+/, '')}`;
}

export function partsFromIti(iti, { separateDialCode = true, input = null } = {}) {
    const resolvedIti = resolveItiFromInput(input) ?? iti;

    if (! resolvedIti) {
        return {
            countryCode: '',
            national: '',
            e164: '',
            valid: false,
            countryName: '',
            iso2: '',
            dialCode: '',
        };
    }

    const data = selectedCountryFromIti(resolvedIti) ?? {};
    const dialCode = String(data.dialCode ?? '');
    const inputEl = inputElementFromIti(resolvedIti, input);
    const rawInput = inputEl?.value ?? '';
    let e164 = numberFromIti(resolvedIti);

    if (e164 === '') {
        e164 = buildE164FromInput(dialCode, rawInput, separateDialCode);
    }

    const { countryCode, national } = splitE164Parts(e164, dialCode);
    const resolvedNational = national !== ''
        ? national
        : normalizeDigits(rawInput).replace(/^0+/, '');
    const resolvedE164 = e164 !== ''
        ? e164
        : buildE164FromInput(dialCode, rawInput, separateDialCode);
    const valid = isItiNumberValid(resolvedIti);

    return {
        countryCode: countryCode || buildCountryCode(dialCode),
        national: resolvedNational,
        e164: resolvedE164,
        valid,
        countryName: data.name ?? '',
        iso2: data.iso2 ?? '',
        dialCode,
        rawInput,
    };
}

export function explainWhatsAppPhoneValidation(parts) {
    if (! parts) {
        return 'missing-parts';
    }

    if (parts.valid) {
        return 'libphonenumber-valid';
    }

    const nationalDigits = normalizeDigits(parts.national);
    const dialDigits = normalizeDigits(parts.dialCode || parts.countryCode);

    if (dialDigits === '') {
        return 'missing-dial-code';
    }

    if (nationalDigits === '') {
        return 'missing-national-number';
    }

    if (parts.iso2 === 'pk' || dialDigits === '92') {
        if (nationalDigits.length !== 10) {
            return `pk-invalid-length-${nationalDigits.length}`;
        }

        if (! nationalDigits.startsWith('3')) {
            return 'pk-must-start-with-3';
        }

        return 'pk-valid';
    }

    if (nationalDigits.length < 7) {
        return 'national-too-short';
    }

    if (nationalDigits.length > 15) {
        return 'national-too-long';
    }

    return 'generic-valid';
}

export function isWhatsAppPhoneValid(parts) {
    if (! parts) {
        return false;
    }

    if (parts.valid) {
        return true;
    }

    return explainWhatsAppPhoneValidation(parts) === 'pk-valid'
        || explainWhatsAppPhoneValidation(parts) === 'generic-valid';
}

export function whatsappPhoneCountryLabel(parts) {
    if (! parts.countryName) {
        return '';
    }

    const dial = parts.countryCode || buildCountryCode(parts.iso2);

    return `${parts.countryName} (${dial})`;
}
