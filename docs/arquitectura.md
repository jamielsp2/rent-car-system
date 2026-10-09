# Arquitectura

## Estructura

```
app/
  Console/Commands/InstallCommand.php   rentcar:install
  Enums/                                BookingStatus, PaymentMethod, PaymentStatus...
  Filament/
    Clusters/Reservas/                  Resumen, Calendario, Hoy (+ widgets)
    Pages/                              Informes, Web pública, Pagos, Contrato, Base de datos
    Resources/                          Vehículos, Reservas, Clientes, catálogos, blog, usuarios...
  Http/Controllers/                     Site, Page, Checkout, Contract, Install, BookingCalendar
  Http/Middleware/EnsureInstalled.php   redirige a /instalar mientras APP_INSTALLED=false
  Models/                               Eloquent (tablas cr_* y generales)
  Services/                             lógica de negocio (ver abajo)
  Support/                              SiteContent, Installer, DatabaseSetup, EnvFile, SqlScript, Media, LocalTime
config/site.php                         contenido por defecto de la web (editable desde el panel)
database/schema/rent_car.sql            esquema completo (27 tablas)
database/migrations/                    una migración que ejecuta el esquema
database/seeders/                       DefaultDataSeeder (base), DemoSeeder (ejemplo)
resources/views/                        site/ (web), components/site, contracts/, install, filament/
deploy/build_package.py                 genera el zip para hosting compartido
```

## Servicios clave

| Servicio | Responsabilidad |
|---|---|
| `RentalQuote` | Precio de un alquiler: días, tarifa, servicios, impuestos publicados, depósito |
| `BookingCheckout` | Crea reservas (web y panel) validando disponibilidad, cliente y pago |
| `BookingStatusService` | Transiciones de estado |
| `ContractService` / `ContractSettings` / `ContractTerms` | Contratos de entrega y devolución, firma, anulación, texto legal |
| `BookingStats` / `ReportStats` | Cifras de los paneles de Reservas e Informes |

## Configuración "white-label"

- Los textos y datos del negocio se guardan en la tabla `settings` (clave/valor): `site_*` (web), `payment_*` (pagos) y `rentcar_*` (contrato y depósito). `Setting::get/put` con caché.
- `App\Support\SiteContent` es el único punto de lectura para vistas y panel: nombre, logo, contacto, hero, "sobre nosotros", redes, copyright y color de marca. Los valores por defecto salen de `config/site.php`.
- El color de marca se inyecta en el layout como `--color-brand` / `--color-brand-dark`.

## Base de datos

27 tablas. Prefijo `cr_` para el dominio de alquiler:

- Vehículos: `cr_cars`, `cr_car_makes`, `cr_car_types`, `cr_car_transmissions`, `cr_car_fuels`, `cr_car_colors`, `cr_cars_colors`, `cr_car_price_ranges`, `cr_car_maintenance_histories` (gastos del vehículo).
- Reservas: `cr_bookings`, `cr_booking_cars`, `cr_booking_service`, `cr_booking_contracts`, `payments`, `cr_invoices`, `cr_invoice_items`, `cr_services`, `cr_customers`.
- Generales: `cr_currencies`, `cr_taxes`, `users`, `settings`, `slugs`, `faqs`, `posts`, `post_categories`, `testimonials`, más `migrations` de Laravel.

Los precios se guardan en la moneda base (USD) y se convierten al mostrarlos. Las columnas de recogida/devolución guardan la hora local del negocio; los `created_at` van en UTC (ver `LocalTime`).

## Instalación

`/instalar` (`InstallController`) → `DatabaseSetup::test` → `DatabaseSetup::save` (escribe `.env` con `EnvFile`) → `Installer::run`: migración (carga `rent_car.sql` por PDO con `SqlScript`), `DefaultDataSeeder`, administrador y, opcionalmente, `DemoSeeder`. `EnsureInstalled` bloquea el resto del sitio hasta terminar y el asistente da 404 después.

## Archivos subidos

Disco `media` (`config/filesystems.php`), raíz `MEDIA_ROOT` (por defecto `public/media`). `App\Support\Media` genera las URL públicas. Los archivos no van en git.

## Decisiones

- Esquema en SQL plano + una migración: instalación rápida y portable a hosting sin SSH.
- Sin cola ni cron: todo es síncrono (`QUEUE_CONNECTION=sync`), apto para hosting compartido.
- Livewire se publica como asset estático (`public/vendor/livewire`) porque algunos hostings devuelven 404 a rutas virtuales `.js`.
