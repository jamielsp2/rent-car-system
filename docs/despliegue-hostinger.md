# Despliegue en Hostinger (y hosting compartido similar)

Guía para entregar el sistema a un cliente nuevo. El sistema se instala solo desde el navegador, así que **no necesitas importar ningún SQL a mano**.

## 1. Preparar el paquete (en tu PC)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan livewire:publish --assets
python deploy/build_package.py --name "Nombre del Cliente"
```

Resultado: `build/rent-car-system.zip`, con un `.env` nuevo (APP_KEY propia, `APP_DEBUG=false`, `APP_INSTALLED=false`), un `.htaccess` que dirige todo a `public/` y permisos 644/755.

> Después de empaquetar vuelve a `composer install` (con dev) si seguirás desarrollando.

## 2. Hostinger

1. **Plan activo**: un plan vencido responde `403 Forbidden` en todo el dominio.
2. En hPanel crea una **base de datos MySQL vacía** y su usuario; anota host (normalmente `localhost`), nombre, usuario y contraseña.
3. *Administrador de archivos* → `public_html`: elimina el `default.php`/index por defecto, sube `rent-car-system.zip` y **Extraer** en `public_html`. Debe quedar `public_html/.htaccess`, `public_html/public/`, `public_html/app/`, etc.
4. Abre el dominio. Verás el asistente `/instalar`: negocio, base de datos y administrador. Si hay un error de conexión se explica y no se guarda nada.
5. Entra a `/admin` y personaliza todo ([personalizacion.md](personalizacion.md)).
6. Activa SSL en hPanel y pon `APP_URL=https://tu-dominio` (el asistente ya lo guarda; el sistema fuerza https y confía en el proxy).

## Alternativa: desplegar con Git (sin zip)

El repositorio ya incluye `vendor/` (sin paquetes de desarrollo), `public/build`, los assets de Livewire y un `.htaccess` en la raíz, así que se puede clonar tal cual:

1. hPanel → *Git*: repositorio `https://github.com/jamielsp2/rent-car-system.git`, rama `main`, directorio vacío (`public_html`).
2. Abre el dominio: en el primer acceso se crea `.env` solo (con su propia `APP_KEY`, producción) y se abre `/instalar`.
3. Si `storage/` o `bootstrap/cache/` no son escribibles, ponles permisos 775.

Para publicar cambios: antes de `git push` ejecuta `composer install --no-dev --optimize-autoloader`, `npm run build` y `php artisan livewire:publish --assets`, y vuelve a `composer install` para seguir desarrollando.

## 3. Fotos

Las fotos subidas desde el admin se guardan en `public/media` (variable `MEDIA_ROOT`). No hay que subir nada: se llenan al usar el panel. Para migrar fotos de otro sitio, extrae un zip con la carpeta `public/media/...` sobre la raíz.

## Problemas conocidos

| Síntoma | Causa y solución |
|---|---|
| `403 Forbidden` en todo el dominio | Plan de hosting vencido, o permisos distintos de 644/755. Reactiva el plan; vuelve a subir el zip generado con `build_package.py` |
| `No input file specified` en `/public/...` | Falta `public_html/.htaccess` en la raíz; el zip lo incluye |
| El login no hace nada / recarga | `/livewire/livewire.js` devuelve 404 en Hostinger. Ejecuta `php artisan livewire:publish --assets` **antes** de empaquetar (queda en `public/vendor/livewire`) |
| Las imágenes no cargan | La carpeta `public/media` falta o `MEDIA_ROOT` apunta a otro sitio; revisa `.env` |
| Estilos sin cargar / http mixto | `APP_URL` debe ser `https://...`; borra `bootstrap/cache/*.php` |
| No se entra con las credenciales de local | La base del servidor es otra: el administrador se crea en el asistente de ese servidor |
| Error 500 sin detalle | Mira `storage/logs/laravel.log`; no actives `APP_DEBUG` en producción más de unos minutos |

## Actualizar un cliente existente

1. Empaqueta de nuevo y sube **solo** `app`, `resources`, `routes`, `config`, `database`, `public/build`, `vendor` (si cambió) por encima, **sin tocar** `.env` ni `public/media`.
2. Si la versión trae migraciones nuevas, ejecútalas con `php artisan migrate --force` (requiere SSH; en planes sin SSH, aplica el SQL de esa migración desde phpMyAdmin).
3. Borra `bootstrap/cache/*.php` y `storage/framework/views/*`.
