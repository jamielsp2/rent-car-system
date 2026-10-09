<?php

/*
 * Default content of the public website. Everything here can be changed from the
 * admin panel (Configuración → Web pública); these are only the values a fresh
 * installation starts with. Saved values live in the `settings` table (`site_*`).
 */
return [

    'name' => env('APP_NAME', 'Rent Car'),

    // Main colour of buttons and highlights on the public site (#rrggbb).
    'brand_color' => '#2563eb',

    'contact' => [
        'phone' => '',
        'whatsapp' => '',
        'email' => '',
        'address' => '',
        'hours' => 'Lun - Sáb, 8:00 - 17:00',
    ],

    'social' => [
        'facebook' => '',
        'instagram' => '',
    ],

    'hero' => [
        'badge' => 'Tu vehículo ideal te espera',
        'title' => 'Alquiler de vehículos',
        'subtitle' => 'Explora nuestra flota y disfruta de un proceso de alquiler sin complicaciones, con asesoría en cada paso.',
        'image' => null,
    ],

    'about' => [
        'title' => 'El carro de tus viajes está aquí',
        'text' => 'Somos más que una empresa de alquiler de vehículos: somos tus compañeros de viaje. Ofrecemos una flota variada, desde autos compactos hasta SUVs, para que cada cliente encuentre la opción perfecta según sus necesidades.',
        'stats' => [
            ['number' => '+100', 'label' => 'Clientes satisfechos', 'image' => null],
            ['number' => '5+', 'label' => 'Años de experiencia', 'image' => null],
            ['number' => '24h', 'label' => 'Asistencia y soporte', 'image' => null],
        ],
    ],

    'invitations' => [
        'title' => 'Alquila el vehículo que necesitas',
        'text' => 'Desde escapadas de fin de semana hasta traslados corporativos, tenemos el vehículo perfecto para tu viaje.',
        'items' => [
            [
                'title' => '¿Listo para tu próxima aventura?',
                'text' => 'Elige entre autos económicos, SUVs espaciosos y más. Con asistencia durante todo el alquiler, viajar con tranquilidad es posible.',
                'button' => 'Reserva ahora',
            ],
            [
                'title' => 'Viaja con estilo y confort',
                'text' => 'Vehículos cómodos y bien cuidados para recorrer cualquier ruta. ¡Convierte cada trayecto en una experiencia inolvidable!',
                'button' => 'Ver vehículos',
            ],
        ],
    ],

    'why_us' => [
        ['icon' => 'map', 'title' => 'Elija una ubicación', 'text' => 'Seleccione el destino ideal para iniciar su viaje con tranquilidad.'],
        ['icon' => 'check', 'title' => 'Precios transparentes', 'text' => 'Disfrute de precios claros y directos, sin sorpresas, para que sepa exactamente lo que paga.'],
        ['icon' => 'calendar', 'title' => 'Reserva cómoda', 'text' => 'Benefíciese de una gran variedad de opciones de alquiler, incluidas las ofertas especiales para estancias cortas, largas y de fin de semana.'],
        ['icon' => 'phone', 'title' => 'Atención al cliente', 'text' => 'Obtenga asistencia siempre que la necesite con nuestro equipo especializado.'],
    ],
];
