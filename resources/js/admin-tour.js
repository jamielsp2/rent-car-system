import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';

/**
 * Guided tour of the admin panel: Informes, Reservas and Vehículos.
 *
 * The panel is not an SPA, so every page loads this script again; progress
 * lives in localStorage (per user) and each section hands over to the next
 * page. It starts by itself the first time, and the topbar button replays it.
 */
const config = document.getElementById('admin-tour-config');

if (config && document.querySelector('.fi-sidebar')) {
    const data = JSON.parse(config.textContent);
    const storageKey = `rentcar-tour:${data.user}`;

    const read = () => {
        try {
            return JSON.parse(localStorage.getItem(storageKey) || 'null');
        } catch {
            return null;
        }
    };
    const write = (state) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch {
            /* private mode: the tour just won't remember its progress */
        }
    };

    const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
    const visible = (el) => !!el && el.getClientRects().length > 0;

    // Widgets load lazily, so look them up by their heading instead of position.
    const widget = (title) => () => [...document.querySelectorAll('.fi-wi-widget')]
        .find((el) => el.textContent.trim().toLowerCase().startsWith(title.toLowerCase()));
    const firstWidget = () => document.querySelector('.fi-wi-widget');
    const q = (selector) => () => document.querySelector(selector);
    const sidebarLink = (path) => () => [...document.querySelectorAll('.fi-sidebar-item a')]
        .find((a) => new URL(a.href).pathname.replace(/\/$/, '') === path);
    const headerButton = (text) => () => [...document.querySelectorAll('.fi-header a, .fi-header button')]
        .find((el) => el.textContent.trim().toLowerCase().includes(text.toLowerCase()));

    const sections = {
        informes: {
            url: data.urls.informes,
            steps: [
                {
                    popover: {
                        title: 'Bienvenido a tu panel',
                        description: 'Te enseñaremos en pocos minutos las tres secciones principales: <strong>Informes</strong>, <strong>Reservas</strong> y <strong>Vehículos</strong>. Puedes salir cuando quieras y volver a verlo con el botón «Tour guiado» de la barra superior.',
                    },
                },
                {
                    element: q('.fi-sidebar-nav'),
                    popover: {
                        title: 'Menú lateral',
                        description: 'Desde aquí te mueves entre las secciones. Solo ves los módulos que tu rol permite.',
                        side: 'right',
                    },
                },
                {
                    element: q('.fi-header'),
                    popover: {
                        title: 'Informes',
                        description: 'Cómo va el negocio de un vistazo: ingresos, utilidad, flota, demanda y clientes.',
                        side: 'bottom',
                    },
                },
                {
                    element: q('.fi-header-actions-ctn'),
                    popover: {
                        title: 'Elige el período',
                        description: 'Cambia entre los últimos 7 o 30 días, este mes, 3 meses, el año o unas fechas a medida. Todas las cifras de la página se ajustan, y se comparan con el período anterior.',
                        side: 'left',
                    },
                },
                {
                    element: firstWidget,
                    popover: {
                        title: 'Indicadores clave',
                        description: 'Ingresos facturados, utilidad neta, reservas y ocupación de la flota, junto a la tarifa diaria, la duración promedio y las cancelaciones.',
                        side: 'bottom',
                    },
                },
                {
                    element: widget('Ingresos, gastos y utilidad'),
                    popover: {
                        title: 'Ingresos, gastos y utilidad',
                        description: 'La evolución del dinero en el período: lo que entra, lo que se gasta en la flota y lo que queda.',
                        side: 'top',
                    },
                },
                {
                    element: widget('Estado de las reservas'),
                    popover: {
                        title: 'Estado de las reservas',
                        description: 'Cuántas reservas hay pendientes, confirmadas, entregadas, completadas o canceladas.',
                        side: 'left',
                    },
                },
                {
                    element: widget('Rendimiento de la flota'),
                    popover: {
                        title: 'Rendimiento de la flota',
                        description: 'Qué vehículos dejan más dinero y cuáles se alquilan menos, una vez descontados sus gastos.',
                        side: 'top',
                    },
                },
            ],
            next: 'Ir a Reservas',
        },
        reservas: {
            url: data.urls.reservas,
            steps: [
                {
                    element: q('.fi-tabs'),
                    popover: {
                        title: 'Reservas',
                        description: 'Cuatro vistas: <strong>Resumen</strong>, <strong>Todas las reservas</strong>, <strong>Calendario</strong> y <strong>Hoy</strong>.',
                        side: 'bottom',
                    },
                },
                {
                    element: firstWidget,
                    popover: {
                        title: 'Resumen del período',
                        description: 'Reservas del período, las que faltan por confirmar, los vehículos rentados ahora y las salidas y regresos de los próximos 7 días.',
                        side: 'bottom',
                    },
                },
                {
                    element: widget('Estado de las reservas'),
                    popover: {
                        title: 'El ciclo de una reserva',
                        description: 'Pendiente → confirmada → entregada → completada, o cancelada. Cada cambio de estado queda registrado.',
                        side: 'left',
                    },
                },
                {
                    element: q('.fi-tabs-item[href*="/reservas/bookings"]'),
                    popover: {
                        title: 'Todas las reservas',
                        description: 'La lista completa: busca, abre una reserva, cambia su estado o crea una nueva.',
                        side: 'bottom',
                    },
                },
                {
                    element: q('.fi-tabs-item[href*="/reservas/calendario"]'),
                    popover: {
                        title: 'Calendario',
                        description: 'Las reservas sobre un calendario, para ver de un vistazo qué vehículos están ocupados y cuándo.',
                        side: 'bottom',
                    },
                },
                {
                    element: q('.fi-tabs-item[href*="/reservas/hoy"]'),
                    popover: {
                        title: 'Hoy',
                        description: 'Las entregas y devoluciones del día. Desde aquí se abre el contrato de entrega o devolución, con la inspección y la firma del cliente.',
                        side: 'bottom',
                    },
                },
            ],
            next: 'Ir a Vehículos',
        },
        vehiculos: {
            url: data.urls.vehiculos,
            steps: [
                {
                    element: headerButton('nuevo'),
                    popover: {
                        title: 'Agrega un vehículo',
                        description: 'Crea la ficha con sus fotos, precios y datos. Los vehículos publicados aparecen en la web para reservar.',
                        side: 'left',
                    },
                },
                {
                    element: q('.fi-ta-filters'),
                    popover: {
                        title: 'Filtros',
                        description: 'Filtra por año de ingresos, por estado y por marca.',
                        side: 'bottom',
                    },
                },
                {
                    element: q('.fi-ta-search-field'),
                    popover: {
                        title: 'Búsqueda',
                        description: 'Encuentra un vehículo escribiendo su nombre o su marca.',
                        side: 'bottom',
                    },
                },
                {
                    element: q('.fi-ta-content'),
                    popover: {
                        title: 'Tu flota',
                        description: 'Por cada vehículo: su estado, la tarifa por día, y en el año elegido los ingresos, los gastos, la rentabilidad y la ocupación. Abre uno para editar sus fotos, precios y gastos.',
                        side: 'top',
                    },
                },
                {
                    popover: {
                        title: 'Listo, ya conoces lo esencial',
                        description: 'Cuando quieras repasarlo, pulsa «Tour guiado» en la barra superior.',
                    },
                },
            ],
            next: 'Terminar',
        },
    };

    const order = data.sections.filter((key) => sections[key]);
    const here = location.pathname.replace(/\/$/, '');
    const sectionHere = order.find((key) => new URL(sections[key].url, location.origin).pathname.replace(/\/$/, '') === here);

    async function run(key) {
        const section = sections[key];

        // Wait for the lazy widgets / table before deciding which steps exist.
        for (let i = 0; i < 20 && ! document.querySelector('.fi-wi-widget .fi-wi-stats-overview-stat, .fi-wi-widget canvas, .fi-wi-widget [class*="fi-section"], .fi-ta-content, .fi-tabs, .fi-ta-empty-state') ; i++) {
            await sleep(250);
        }
        await sleep(400);

        const steps = section.steps.filter((step) => {
            if (! step.element) return true;
            const el = step.element();
            return visible(el);
        });

        const index = order.indexOf(key);
        const nextKey = order[index + 1];
        let advancing = false;

        const finish = () => {
            advancing = true;
            if (nextKey) {
                write({ status: 'active', section: nextKey });
                location.href = sections[nextKey].url;
            } else {
                write({ status: 'done' });
            }
            tour.destroy();
        };

        const tour = driver({
            showProgress: true,
            allowClose: true,
            overlayOpacity: 0.55,
            stagePadding: 6,
            stageRadius: 10,
            nextBtnText: 'Siguiente',
            prevBtnText: 'Anterior',
            doneBtnText: section.next,
            progressText: '{{current}} de {{total}}',
            steps: steps.map((step) => ({
                ...step,
                element: step.element ? step.element() : undefined,
            })),
            onNextClick: (element, step, { driver: d }) => {
                if (d.isLastStep()) {
                    finish();
                } else {
                    d.moveNext();
                }
            },
            onDestroyStarted: () => {
                if (! advancing) write({ status: 'done' });
                tour.destroy();
            },
        });

        tour.drive();
    }

    // The topbar button restarts from the first section.
    document.querySelectorAll('[data-tour-start]').forEach((button) => {
        button.addEventListener('click', () => {
            if (! order.length) return;
            write({ status: 'active', section: order[0] });
            if (sectionHere === order[0]) {
                run(order[0]);
            } else {
                location.href = sections[order[0]].url;
            }
        });
    });

    const state = read();

    if (order.length) {
        if (! state && sectionHere === order[0]) {
            // First visit: start on the first section's page.
            write({ status: 'active', section: order[0] });
            run(order[0]);
        } else if (state?.status === 'active' && state.section === sectionHere) {
            run(sectionHere);
        }
    }
}
