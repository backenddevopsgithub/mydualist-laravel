import flatpickr from 'flatpickr';

export function initOnboardingDatePickers() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    if (! startInput || ! endInput) {
        return;
    }

    let endPicker;
    const syncInput = (input) => {
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    flatpickr(startInput, {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true,
        appendTo: document.body,
        onChange(selectedDates) {
            if (selectedDates[0] && endPicker) {
                endPicker.set('minDate', selectedDates[0]);
            }

            syncInput(startInput);
        },
    });

    endPicker = flatpickr(endInput, {
        dateFormat: 'Y-m-d',
        minDate: startInput.value || 'today',
        disableMobile: true,
        appendTo: document.body,
        onChange() {
            syncInput(endInput);
        },
    });
}
