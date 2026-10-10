# Almacenamiento de fotos y contratos (Cloudflare R2)

Cada cliente tiene **su propio bucket** en Cloudflare R2. Ahí van las fotos de los vehículos, el blog, los logos y los archivos de los contratos (fotos de entrega y devolución, licencias de conducir). Si un sistema no tiene bucket, todo sigue guardándose en `public/media` como antes.

## Cómo llega el bucket al sistema del cliente

1. En la plataforma de licencias creas la licencia con la casilla **«Crear su almacenamiento en Cloudflare R2»** (o, después, el botón *Crear bucket y credencial* en su ficha).
2. La plataforma crea el bucket `rcs-<cliente>-<código>` y una credencial S3 **limitada a ese bucket**: no puede leer ni escribir en otros buckets ni listar la cuenta.
3. El cliente pega su código de licencia en `/licencia`. El sistema se presenta a la plataforma con el código firmado y recibe **solo las credenciales de su bucket** (`storage/app/r2.json`, permisos 600). Se vuelven a pedir cada 12 horas, así que si las renuevas desde la plataforma llegan solas.

La credencial de Cloudflare de la plataforma **nunca llega al sistema del cliente**.

### A mano (sin plataforma)
Define en `.env`:
```
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_BUCKET=nombre-del-bucket
R2_KEY=...
R2_SECRET=...
```
Tienen prioridad sobre lo que envíe la plataforma. Usa siempre una credencial limitada a un solo bucket.

## Qué es público y qué es privado

El bucket es **privado**: nadie accede a él directamente. La app sirve los archivos por `/media-r2?f=<ruta>`:

| Carpeta | Acceso |
|---|---|
| `cars/`, `blog/`, `services/`, `testimonials/`, `site/` | Público (fotos de la web), con caché de un año |
| Todo lo demás (`car-manager/contract-images`, `car-manager/return-images`, licencias de conducir…) | **Solo con URL firmada** que genera el panel y caduca a las 12 horas |

La lista pública está en `App\Support\R2::PUBLIC_FOLDERS`. Los archivos se sirven con `X-Content-Type-Options: nosniff` y una CSP restrictiva, para que un SVG subido no pueda ejecutar scripts.

## Cómo configurar la plataforma (una vez)

1. En Cloudflare → *Manage account* → *Account API tokens* → crea un token con **solo** estos permisos de cuenta: **Workers R2 Storage: Edit** y **Account API Tokens: Edit**. No uses un token con más permisos.
2. En la plataforma → *Ajustes* → *Cloudflare R2*: pega el **ID de cuenta** y el token. Se comprueba al guardar.

## Archivos que ya existían en el servidor
Para un negocio que ya tenía fotos en `public/media`:
```bash
php artisan media:to-r2            # sube todo
php artisan media:to-r2 --delete   # sube y borra el local
```

## Rotar la credencial de un cliente
En la ficha de la licencia → *Renovar credencial*. La nueva llega al cliente en su próxima consulta (máx. 30 min) y la anterior sigue valiendo hasta la siguiente rotación, para no dejarlo sin archivos entre medias.

## Qué pasa al borrar o revocar una licencia
Los datos **no se borran** ni se corta el acceso: el sistema del cliente sigue funcionando con las credenciales que ya tiene (solo ve el aviso «tenant no activado»). La plataforma deja de entregar credenciales nuevas a una licencia revocada o vencida (con 7 días de gracia). El bucket y sus archivos se conservan en Cloudflare; si el cliente se da de baja, bórralo a mano en Cloudflare y revoca su credencial.
