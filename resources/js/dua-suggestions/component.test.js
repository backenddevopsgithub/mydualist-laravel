import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { createPublicSubmissionForm } from './component';

function createForm(overrides = {}) {
    const form = createPublicSubmissionForm({
        step: 'duas',
        slug: 'arsalan-hajj-2',
        duas: [''],
        firstName: 'Amina',
        ...overrides,
    });

    form.$nextTick = (callback) => callback?.();
    form.$watch = () => () => {};

    return form;
}

function mockDocumentFocus() {
    const focus = vi.fn();

    vi.stubGlobal('document', {
        getElementById: vi.fn(() => ({
            querySelector: vi.fn(() => ({ focus })),
            scrollIntoView: vi.fn(),
        })),
    });

    return focus;
}

describe('createPublicSubmissionForm', () => {
    afterEach(() => {
        vi.unstubAllGlobals();
    });

    beforeEach(() => {
        mockDocumentFocus();
    });

    it('loads and maps Quran suggestions when the user reaches the duas step', async () => {
        const form = createForm({ step: 'info' });

        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({
                message: 'List suggestions retrieved.',
                data: {
                    general: [],
                    quran: [
                        { id: 3, title: 'Dua 31', content: 'Dua 33', source_type: 'quran' },
                        { id: 2, title: 'Quranic Dua', content: 'Rabbana atina fid dunya hasanah.', source_type: 'quran' },
                    ],
                    sunnah: [],
                },
            }),
        });

        vi.stubGlobal('fetch', fetchMock);

        form.init();
        expect(fetchMock).not.toHaveBeenCalled();

        await form.acceptGuide();

        expect(form.suggestions.quran).toHaveLength(2);
        expect(form.activeDuaIndex).toBe(0);
    });

    it('inserts suggestion text into the first dua field by default', () => {
        const form = createForm();

        form.selectSuggestion({
            id: 3,
            title: 'Dua 31',
            content: 'May Allah bless {name}.',
        });

        expect(form.duas[0]).toBe('May Allah bless Amina.');
        expect(form.selectedSuggestionIds).toEqual([3]);
    });

    it('inserts suggestion text into the second and third dua fields', () => {
        const form = createForm({
            duas: ['First dua', 'Second dua', ''],
        });

        form.setActiveDuaIndex(1);
        form.selectSuggestion({
            id: 4,
            title: 'Second field suggestion',
            content: 'For the second field.',
        });

        expect(form.duas[0]).toBe('First dua');
        expect(form.duas[1]).toBe('Second dua\n\nFor the second field.');

        form.setActiveDuaIndex(2);
        form.selectSuggestion({
            id: 5,
            title: 'Third field suggestion',
            content: 'For the third field.',
        });

        expect(form.duas[2]).toBe('For the third field.');
        expect(form.selectedSuggestionIds).toEqual([4, 5]);
    });

    it('updates the active index when switching between fields', () => {
        const form = createForm({
            duas: ['First dua', 'Second dua'],
        });

        form.setActiveDuaIndex(1);

        expect(form.activeDuaIndex).toBe(1);
    });

    it('updates the active index when removing the active field', () => {
        const form = createForm({
            duas: ['First dua', 'Second dua', 'Third dua'],
            activeDuaIndex: 2,
        });

        form.removeDua(2);

        expect(form.duas).toEqual(['First dua', 'Second dua']);
        expect(form.activeDuaIndex).toBe(1);
    });

    it('falls back to the first field when the active index is invalid', () => {
        const form = createForm({
            duas: ['First dua', 'Second dua'],
            activeDuaIndex: 99,
        });

        form.selectSuggestion({
            id: 6,
            title: 'Fallback suggestion',
            content: 'Inserted into the first field.',
        });

        expect(form.duas[0]).toBe('First dua\n\nInserted into the first field.');
        expect(form.activeDuaIndex).toBe(0);
    });

    it('makes a newly added dua field active', () => {
        const form = createForm({
            duas: ['First dua'],
            activeDuaIndex: 0,
        });

        form.addDua();

        expect(form.duas).toHaveLength(2);
        expect(form.activeDuaIndex).toBe(1);
    });

    it('restores persisted whatsapp phone values from config', () => {
        const form = createForm({
            whatsapp: true,
            whatsappCountryCode: '+92',
            whatsappPhone: '3001234567',
        });

        expect(form.whatsappCountryCode).toBe('+92');
        expect(form.whatsappPhone).toBe('3001234567');
        expect(form.whatsappE164).toBe('+923001234567');
    });

    it('blocks otp send until the whatsapp number is valid', async () => {
        const form = createForm({
            whatsapp: true,
            whatsappPhoneValid: false,
        });

        form.syncWhatsAppPhoneFromInput = () => {
            form.whatsappPhoneValid = false;
            form.whatsappCountryCode = '+44';
            form.whatsappPhone = '12';
        };

        const fetchMock = vi.fn();
        vi.stubGlobal('fetch', fetchMock);

        await form.sendWhatsAppOtp();

        expect(fetchMock).not.toHaveBeenCalled();
        expect(form.whatsappOtpError).toBe('Error: Invalid Phone Number');
    });

    it('sends otp using derived country code and national number', async () => {
        const form = createForm({
            whatsapp: true,
        });

        form.syncWhatsAppPhoneFromInput = () => {
            form.whatsappPhoneValid = true;
            form.whatsappCountryCode = '+92';
            form.whatsappPhone = '3001234567';
        };

        const fetchMock = vi.fn().mockResolvedValue({
            ok: true,
            json: async () => ({ data: { expires_in: 300, otp_length: 6 } }),
        });

        vi.stubGlobal('fetch', fetchMock);
        vi.stubGlobal('document', {
            querySelector: vi.fn(() => ({ getAttribute: () => 'token' })),
        });

        await form.sendWhatsAppOtp();

        expect(fetchMock).toHaveBeenCalledWith('/api/v1/public/submissions/otp/send', expect.objectContaining({
            method: 'POST',
            body: JSON.stringify({
                whatsapp_country_code: '+92',
                whatsapp_phone: '3001234567',
            }),
        }));
        expect(form.whatsappOtpStep).toBe('otp');
    });
});
