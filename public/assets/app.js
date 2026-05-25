document.addEventListener('DOMContentLoaded', function () {
    const today = new Date().toISOString().slice(0, 10);

    const cfg = {
        locale: 'ru',
        dateFormat: 'Y-m-d',
        maxDate: today,
        allowInput: true,
    };

    const fromPicker = flatpickr('#date-from', cfg);
    const toPicker   = flatpickr('#date-to',   cfg);

    // Кап 30 дней
    document.querySelector('form').addEventListener('submit', function () {
        const from = fromPicker.selectedDates[0];
        const to   = toPicker.selectedDates[0];
        if (from && to) {
            const diffDays = (to - from) / 86400000;
            if (diffDays > 30) {
                const capped = new Date(to);
                capped.setDate(capped.getDate() - 30);
                fromPicker.setDate(capped);
            }
        }
    });
});
