import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Inline range calendar for the car page. Booked nights are disabled and
 * marked; picking a range that crosses them is refused.
 */
const element = document.getElementById('rental-calendar');

if (element) {
    const form = document.getElementById('quote-form');
    const fromInput = form.querySelector('input[name=from]');
    const toInput = form.querySelector('input[name=to]');
    const summary = document.getElementById('calendar-summary');
    const warning = document.getElementById('calendar-warning');
    const booked = JSON.parse(element.dataset.booked || '[]');

    // Texts and calendar language come from the page (data-i18n), Spanish when absent.
    const t = {
        locale: 'es', day: 'día', days: 'días', departure: 'Salida', chooseReturn: 'Ahora elige la devolución.',
        choose: 'Elige la fecha de salida y luego la de devolución.', unavailable: 'No disponible',
        crossed: 'El vehículo no está disponible en parte de esas fechas. Elige otro rango.',
        ...JSON.parse(element.dataset.i18n || '{}'),
    };
    const language = t.locale === 'es' ? Spanish : undefined;

    const minDays = Math.max(1, parseInt(element.dataset.minDays || '1', 10));
    const toKey = (date) => flatpickr.formatDate(date, 'Y-m-d');
    const addDays = (date, days) => { const d = new Date(date); d.setDate(d.getDate() + days); return d; };
    const isBookedDay = (date) => booked.some(({ from, to }) => toKey(date) >= from && toKey(date) <= to);
    const nice = (date) => flatpickr.formatDate(date, 'D j M Y', language ?? flatpickr.l10ns.default);

    const crossesBooked = (start, end) => booked.some(({ from, to }) => from < toKey(end) && to >= toKey(start));

    // First free stretch of the minimum length, starting tomorrow.
    const firstFreeRange = () => {
        for (let start = addDays(new Date(), 1), tries = 0; tries < 365; start = addDays(start, 1), tries++) {
            const end = addDays(start, minDays);
            if (!isBookedDay(start) && !crossesBooked(start, end)) return [start, end];
        }
        return [];
    };

    const requested = [fromInput.value, toInput.value].filter(Boolean).map((v) => new Date(v + 'T00:00:00'));
    const usable = requested.length === 2 && !isBookedDay(requested[0]) && !crossesBooked(requested[0], requested[1]);
    const initial = usable ? requested : firstFreeRange();

    const setWarning = (text) => {
        warning.textContent = text || '';
        warning.classList.toggle('hidden', !text);
    };

    const showSummary = (dates) => {
        if (dates.length === 2) {
            const nights = Math.round((dates[1] - dates[0]) / 86400000);
            summary.innerHTML = `<strong>${nice(dates[0])}</strong> → <strong>${nice(dates[1])}</strong> · ${nights} ${nights === 1 ? t.day : t.days}`;
        } else if (dates.length === 1) {
            summary.innerHTML = `${t.departure}: <strong>${nice(dates[0])}</strong>. ${t.chooseReturn}`;
        } else {
            summary.textContent = t.choose;
        }
    };

    const picker = flatpickr(element, {
        inline: true,
        mode: 'range',
        locale: language ?? 'default',
        minDate: 'today',
        dateFormat: 'Y-m-d',
        disable: booked,
        defaultDate: initial,
        onDayCreate(_, __, ___, day) {
            if (day.classList.contains('flatpickr-disabled') && !day.classList.contains('prevMonthDay') && !day.classList.contains('nextMonthDay')) {
                const key = toKey(day.dateObj);
                if (booked.some(({ from, to }) => key >= from && key <= to)) {
                    day.classList.add('is-booked');
                    day.title = t.unavailable;
                }
            }
        },
        onChange(dates, _, instance) {
            setWarning('');

            if (dates.length === 2 && toKey(dates[0]) === toKey(dates[1])) {
                // Same day twice: treat it as a fresh pickup date.
                instance.setDate([dates[0]], false);
                dates = [dates[0]];
            }

            if (dates.length === 2 && crossesBooked(dates[0], dates[1])) {
                setWarning(t.crossed);
                instance.setDate([dates[0]], false);
                dates = [dates[0]];
            }

            showSummary(dates);

            if (dates.length === 2) {
                fromInput.value = toKey(dates[0]);
                toInput.value = toKey(dates[1]);
                form.dispatchEvent(new Event('change', { bubbles: true }));
            }
        },
    });

    showSummary(picker.selectedDates);

    // When the suggested dates differ from the page's, quote the suggestion.
    if (initial.length === 2 && (toKey(initial[0]) !== fromInput.value || toKey(initial[1]) !== toInput.value)) {
        fromInput.value = toKey(initial[0]);
        toInput.value = toKey(initial[1]);
        form.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
