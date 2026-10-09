# Rent Car System

Sistema completo para una empresa de **alquiler de vehículos**: web pública con reservas en línea, panel de administración, contratos de entrega y devolución con firma del cliente, informes y configuración de marca ("white-label") para desplegarlo a distintos clientes sin tocar código.

Hecho con **Laravel**, **Filament 5**, Livewire, Tailwind CSS 4 y MySQL/MariaDB. Interfaz en español.

## Qué incluye

**Web pública**
- Inicio con hero, vehículos destacados, ¿por qué elegirnos? y testimonios; catálogo con filtros; ficha del vehículo con precio automático y calendario de fechas ocupadas.
- Reserva en línea (pago contra entrega o transferencia), confirmación, blog, preguntas frecuentes, sobre nosotros y selector de moneda.
- Responsive: móvil, tablet vertical y escritorio.

**Panel de administración (`/admin`)**
- **Reservas**: resumen con gráficos, calendario, entregas de hoy y estados (pendiente → confirmada → entregada → completada / cancelada).
- **Contratos**: contrato de entrega y de devolución con inspección del vehículo, firma del cliente (en tablet) e impresión.
- **Vehículos**: fichas con fotos, precios, gastos por vehículo y rentabilidad.
- **Informes**: ingresos, gastos, rentabilidad, ocupación y más.
- **Clientes, servicios adicionales, blog, FAQ, testimonios, impuestos, monedas, usuarios** y catálogos (marcas, tipos, transmisiones, combustibles, colores).
- **Configuración**: marca y datos del negocio, métodos de pago, texto del contrato y conexión a la base de datos.

**Instalación guiada**: asistente web en `/instalar` o comando `php artisan rentcar:install`.

## Inicio rápido (local)

Requisitos: PHP 8.3+, Composer, Node 20+, MySQL/MariaDB.

```bash
git clone https://github.com/jamielsp2/rent-car-system.git
cd rent-car-system
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
# crea una base de datos vacía (p. ej. rent_car) y ajusta DB_* en .env
php artisan rentcar:install --site="Mi Negocio" --name="Admin" --email=admin@ejemplo.com --password=cambia-esto --demo
php artisan serve
```

- Web: http://127.0.0.1:8000 · Panel: http://127.0.0.1:8000/admin
- Quita `--demo` para una instalación **limpia** (sin vehículos ni reservas de ejemplo).
- Sin esos argumentos, abre el sitio y sigue el asistente en `/instalar`.

## Documentación

| Documento | Contenido |
|---|---|
| [docs/instalacion.md](docs/instalacion.md) | Instalación local, asistente web, comando y variables de entorno |
| [docs/despliegue-hostinger.md](docs/despliegue-hostinger.md) | Subir un cliente nuevo a Hostinger / hosting compartido y problemas conocidos |
| [docs/personalizacion.md](docs/personalizacion.md) | Marca, colores, textos, pagos, contrato, monedas e impuestos de cada cliente |
| [docs/operacion.md](docs/operacion.md) | Uso diario: reservas, entregas, contratos e informes |
| [docs/arquitectura.md](docs/arquitectura.md) | Estructura del código, base de datos y servicios |
| [docs/desarrollo.md](docs/desarrollo.md) | Tests, convenciones y cómo extender |
| [docs/seguridad.md](docs/seguridad.md) | Buenas prácticas y puntos a revisar antes de entregar |
| [CHANGELOG.md](CHANGELOG.md) | Historial de cambios |

## Desplegar a un cliente nuevo (resumen)

1. `composer install --no-dev -o && npm ci && npm run build && php artisan livewire:publish --assets`
2. `python deploy/build_package.py --name "Nombre del Cliente"` → `build/rent-car-system.zip`
3. Subir y descomprimir en el hosting, crear la base de datos vacía, abrir el dominio y completar `/instalar`.
4. Entrar a `/admin` → *Configuración* y personalizar marca, contacto, pagos y contrato.

Detalle completo en [docs/despliegue-hostinger.md](docs/despliegue-hostinger.md).

## Licencia

Proyecto privado de su autor. Define la licencia antes de compartirlo con terceros.
