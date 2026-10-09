# Desarrollo

## Tests

```bash
composer test          # o: php artisan test
```

- PHPUnit contra una base MySQL dedicada, `rent_car_test` (ver `phpunit.xml`). Créala vacía una vez: `CREATE DATABASE rent_car_test CHARACTER SET utf8mb4;` y asegúrate de que el usuario de `.env` tiene acceso.
- `tests/TestCase.php` reconstruye esa base **una vez por ejecución** (`migrate:fresh` + `DemoSeeder` + un administrador de prueba); cada test corre dentro de una transacción (`DatabaseTransactions`).
- Cobertura: sitio público, reservas y calendario, contratos, monedas, informes, páginas del panel, instalador y utilidades.

Estado actual: 71 tests, todos en verde.

## Comandos útiles

| Comando | Para qué |
|---|---|
| `composer dev` | Servidor + Vite en desarrollo |
| `npm run build` | Compila assets (Tailwind/Vite) |
| `php artisan rentcar:install --demo` | Instala con datos de ejemplo |
| `php artisan migrate:fresh --seed` | Reinicia la base local con datos de ejemplo (`DatabaseSeeder`) |
| `vendor/bin/pint` | Formato de código |
| `php artisan livewire:publish --assets` | Publica Livewire como archivo estático (necesario en hosting compartido) |

## Convenciones

- Interfaz y mensajes en español; código y comentarios en inglés.
- Lógica de negocio en `app/Services`, no en controladores ni en páginas de Filament.
- Todo texto del negocio debe poder editarse desde el panel: añade la clave a `SiteSettings::KEYS` y lee el valor con `SiteContent`.
- "Hoy" y "ahora" junto a alquileres se calculan con `LocalTime`, nunca con `now()` a secas.
- Cambios de esquema: edita `database/schema/rent_car.sql` para instalaciones nuevas **y** añade una migración incremental para las existentes.

## Cómo extender

- **Nuevo catálogo**: copia un recurso simple (`app/Filament/Resources/CarColors`) y regístralo.
- **Nuevo informe**: añade un widget a `App\Filament\Pages\Reports` usando `ReportStats`.
- **Nuevo método de pago**: caso en `PaymentMethod`, opción en `PaymentSettings` y en `BookingCheckout`.
