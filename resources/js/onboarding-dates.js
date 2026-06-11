import flatpickr from 'flatpickr';

export function initOnboardingDatePickers() {
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');

    if (! startInput || ! endInput) {
        return;
    }

    let endPicker;

    flatpickr(startInput, {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        disableMobile: true,
        appendTo: document.body,
        onChange(selectedDates) {
            if (selectedDates[0] && endPicker) {
                endPicker.set('minDate', selectedDates[0]);
            }
        },
    });

    endPicker = flatpickr(endInput, {
        dateFormat: 'Y-m-d',
        minDate: startInput.value || 'today',
        disableMobile: true,
        appendTo: document.body,
    });
}
