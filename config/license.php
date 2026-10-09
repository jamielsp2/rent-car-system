<?php

/**
 * Licencia del sistema. El código que entrega la plataforma de licencias va firmado
 * (Ed25519); aquí solo vive la clave PÚBLICA para comprobarlo. Se pega en /licencia.
 */
return [
    // Clave pública de tu plataforma de licencias (Ajustes → Clave pública).
    'public_key' => 'L5QvkQKZI8ihnbKKfmHWbIsrDUb5qaRslSLF11hJ6d0=',

    // Días que el sistema sigue funcionando tras el vencimiento, para dar tiempo a renovar.
    'grace_days' => 3,

    // Cada cuántas horas consulta a la plataforma (renovaciones y revocaciones).
    'check_hours' => 12,
];
