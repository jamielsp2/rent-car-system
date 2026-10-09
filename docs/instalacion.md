# Instalación

## Requisitos

- PHP 8.3+ con extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `zip`.
- Composer 2, Node 20+ y npm (solo para compilar los assets).
- MySQL 5.7+ / MariaDB 10.4+ (utf8mb4).

## Opción A: asistente web

1. `composer install`, `npm install && npm run build`.
2. `cp .env.example .env && php artisan key:generate`.
3. En `.env` pon `APP_INSTALLED=false` (así todas las rutas llevan al asistente).
4. Sirve el proyecto (`php artisan serve`) y abre `/instalar`.
5. Completa tres bloques: **negocio** (nombre y URL), **base de datos MySQL** (se prueba la conexión antes de guardar nada) y **administrador**. Opcionalmente marca *cargar datos de ejemplo*.

Al terminar el asistente escribe la conexión en `.env`, crea las tablas, carga los datos iniciales, crea al administrador y pone `APP_INSTALLED=true`. Desde ese momento `/instalar` responde 404.

## Opción B: comando

```bash
php artisan rentcar:install --site="Mi Negocio" --name="Ana Pérez" --email=ana@ejemplo.com --password=una-clave-larga [--demo]
```

La base de datos debe existir y estar configurada en `.env`. El comando es idempotente con los datos base (no pisa lo que el negocio ya editó); falla si el correo del administrador ya existe.

## Qué se crea

- 27 tablas (`database/schema/rent_car.sql`, ejecutado por la migración `create_rent_car_schema`).
- Datos base (`DefaultDataSeeder`): monedas USD (base), DOP y EUR; impuesto ITBIS; marcas, tipos, transmisiones, combustibles y colores; preguntas frecuentes; ajustes por defecto.
- `--demo` añade (`DemoSeeder`): 10 vehículos, 3 clientes, servicios adicionales, reservas en todos los estados, gastos, artículos de blog y testimonios. **Nunca lo uses en la base de un cliente real.**

## Variables de entorno relevantes

| Variable | Uso |
|---|---|
| `APP_NAME` | Nombre del negocio por defecto (se puede cambiar en *Configuración → Web pública*) |
| `APP_URL` | URL pública; si empieza por `https` se fuerza HTTPS |
| `BUSINESS_TIMEZONE` | Zona horaria del negocio para fechas de alquiler y "hoy" (por defecto `America/Santo_Domingo`) |
| `APP_INSTALLED` | `false` activa el asistente `/instalar` |
| `DB_*` | Conexión MySQL (también editable en *Configuración → Base de datos*) |
| `MEDIA_ROOT` | Carpeta de fotos y archivos subidos. Por defecto `public/media` (relativa al proyecto); admite ruta absoluta |

## Desarrollo

`composer dev` arranca servidor, cola y Vite. `composer test` ejecuta la suite (ver [desarrollo.md](desarrollo.md)).
