# Seguridad y revisión antes de entregar

## Checklist por cliente

- [ ] `APP_DEBUG=false` y `APP_ENV=production` (el paquete ya lo trae).
- [ ] `APP_KEY` única por cliente (el paquete genera una nueva; nunca reutilices la de otro cliente).
- [ ] Contraseña del administrador larga y única; crea usuarios nominales para el personal (*Configuración → Usuarios*).
- [ ] Usuario de base de datos propio del cliente, con permisos solo sobre su base. **No pegues contraseñas en chats, tickets ni commits**; si ocurrió, cámbialas.
- [ ] HTTPS activo y `APP_URL` con `https://`.
- [ ] `.env`, `storage/` y el código fuera del acceso web: el `.htaccess` incluido bloquea `.env`/`.git` y sirve solo `public/`. Comprueba que `https://dominio/.env` da 403/404.
- [ ] Copias periódicas de la base de datos y de `public/media`.
- [ ] Revisar y adaptar el **contrato** al país y a la ley local con un abogado (el texto de ejemplo es de República Dominicana).
- [ ] No cargar el `DemoSeeder` en producción.

## Qué está protegido

- Panel `/admin` y contratos detrás de login; el asistente `/instalar` responde 404 una vez instalado y está limitado por `throttle`.
- Checkout con `throttle:10,1`, validación de disponibilidad en servidor y formularios con CSRF.
- Contraseñas con bcrypt; el campo de contraseña de la conexión a la base de datos nunca se muestra de vuelta.
- `.env` solo se reescribe tras probar la conexión con éxito.

## Puntos a tener presentes

- Sin pasarela de pago en línea: se registran pagos contra entrega o transferencia; la conciliación es manual.
- Sin cola ni cron: los correos (si los configuras con `MAIL_*`) se envían de forma síncrona.
- Las fotos de firmas y documentos del cliente se guardan en `public/media` (accesibles por URL si alguien la conoce). Si el cliente exige más privacidad, mueve `MEDIA_ROOT` fuera de `public` y sírvelo mediante una ruta autenticada.
- Mantén actualizadas las dependencias: `composer audit` y `npm audit` periódicamente.
