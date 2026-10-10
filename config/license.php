<?php

/**
 * Licencia del sistema. El código que entrega la plataforma de licencias va firmado
 * (Ed25519); aquí solo vive la clave PÚBLICA para comprobarlo. Se pega en /licencia.
 */
return [
    // Clave pública de tu plataforma de licencias (Ajustes → Clave pública).
    'public_key' => 'L5QvkQKZI8ihnbKKfmHWbIsrDUb5qaRslSLF11hJ6d0=',

    // Días tras el vencimiento en que el aviso del panel es amarillo (después, rojo). El sistema nunca se bloquea.
    'grace_days' => 3,

    // Cada cuántos minutos consulta a la plataforma (renovaciones y revocaciones), siempre después de enviar la respuesta.
    'check_minutes' => 30,
];
