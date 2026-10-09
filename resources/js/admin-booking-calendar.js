import { Calendar } from '@fullcalendar/core';
import esLocale from '@fullcalendar/core/locales/es';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

/**
 * Bookings calendar in the admin. Events come from the calendar feed and
 * follow the vehicle / cancelled filters above it.
 */
function init() {
    const element = document.getElementById('booking-calendar');
    if (!element || element.dataset.ready) return;
    element.dataset.ready = '1';

    const carFilter = document.getElementById('calendar-car');
    const cancelledFilter = document.getElementById('calendar-cancelled');

    const calendar = new Calendar(element, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
        locale: esLocale,
        initialView: window.innerWidth < 768 ? 'listMonth' : 'dayGridMonth',
        height: 'auto',
        firstDay: 1,
        dayMaxEvents: 4,
        nowIndicator: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth',
        },
        buttonText: { listMonth: 'Lista' },
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        events: {
            url: element.dataset.feed,
            extraParams: () => ({
                car: carFilter?.value || '',
                cancelled: cancelledFilter?.checked ? 1 : 0,
            }),
        },
        eventDidMount(info) {
            const { number, status, phone } = info.event.extendedProps;
            info.el.title = `${number} · ${status}\n${info.event.title}${phone ? '\n' + phone : ''}`;
        },
    });

    calendar.render();

    carFilter?.addEventListener('change', () => calendar.refetchEvents());
    cancelledFilter?.addEventListener('change', () => calendar.refetchEvents());
}

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('livewire:navigated', init);
init();
