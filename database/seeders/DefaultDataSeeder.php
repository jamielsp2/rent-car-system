<?php

namespace Database\Seeders;

use App\Models\CarColor;
use App\Models\CarFuel;
use App\Models\CarMake;
use App\Models\CarTransmission;
use App\Models\CarType;
use App\Models\Currency;
use App\Models\Faq;
use App\Models\Setting;
use App\Models\Tax;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Data a new installation starts with. Idempotent: it only adds what is
 * missing and never overwrites what the business has already edited.
 */
class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->currencies();
        $this->taxes();
        $this->catalogs();
        $this->settings();
        $this->faqs();
    }

    protected function currencies(): void
    {
        // USD is the base currency: every price is stored in it. The rates of the
        // others are approximate; the business updates them in Configuración → Monedas.
        $rows = [
            ['title' => 'USD', 'symbol' => '$', 'is_prefix_symbol' => 1, 'decimals' => 2, 'order' => 0, 'is_default' => 1, 'exchange_rate' => 1, 'space_between_price_and_currency' => 0],
            ['title' => 'DOP', 'symbol' => 'RD$', 'is_prefix_symbol' => 1, 'decimals' => 2, 'order' => 1, 'is_default' => 0, 'exchange_rate' => 60, 'space_between_price_and_currency' => 0],
            ['title' => 'EUR', 'symbol' => '€', 'is_prefix_symbol' => 0, 'decimals' => 2, 'order' => 2, 'is_default' => 0, 'exchange_rate' => 0.92, 'space_between_price_and_currency' => 1],
        ];

        foreach ($rows as $row) {
            if (! DB::table('cr_currencies')->where('title', $row['title'])->exists()) {
                DB::table('cr_currencies')->insert($row + ['number_format_style' => 'western', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    protected function taxes(): void
    {
        // Applied to every rental while it is published (Catálogos → Impuestos).
        Tax::query()->firstOrCreate(['name' => 'ITBIS'], [
            'percentage' => 18,
            'amount' => 0,
            'amount_type' => 'once',
            'status' => 'published',
            'priority' => 1,
        ]);
    }

    protected function catalogs(): void
    {
        $lists = [
            CarType::class => ['SUV', 'Sedán', 'Hatchback', 'Minivan', 'Pickup', 'Convertible'],
            CarTransmission::class => ['Automática', 'Manual'],
            CarFuel::class => ['Gasolina', 'Diésel', 'Híbrido', 'Eléctrico'],
            CarColor::class => ['Blanco', 'Negro', 'Gris', 'Plata', 'Rojo', 'Azul', 'Verde', 'Beige'],
            CarMake::class => [
                'Toyota', 'Honda', 'Hyundai', 'Kia', 'Nissan', 'Mazda', 'Mitsubishi', 'Suzuki', 'Chevrolet', 'Ford',
                'Jeep', 'Dodge', 'Volkswagen', 'BMW', 'Mercedes-Benz', 'Audi', 'Lexus', 'Land Rover', 'Changan', 'Geely',
            ],
        ];

        foreach ($lists as $model => $names) {
            foreach ($names as $position => $name) {
                $model::query()->firstOrCreate(['name' => $name], ['status' => 'published', 'order' => $position]);
            }
        }
    }

    protected function settings(): void
    {
        $defaults = [
            // Payment methods offered at checkout (Configuración → Pagos).
            'payment_cod_status' => '1',
            'payment_cod_description' => 'Pagas al recibir el vehículo.',
            'payment_bank_transfer_status' => '0',
            'site_default_currency' => 'USD',
        ];

        foreach ($defaults as $key => $value) {
            Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget('settings.all');
    }

    protected function faqs(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $faqs = [
            ['¿Qué necesito para alquilar un vehículo?', '<p>Para alquilar un vehículo necesitas:</p><ul><li>Tener al menos 21 años de edad</li><li>Presentar una licencia de conducir vigente (nacional o internacional)</li><li>Presentar un documento de identidad o pasaporte</li><li>Dejar un depósito de garantía</li></ul>'],
            ['¿Cómo funciona el depósito de garantía?', '<p>Es un monto reembolsable que se retiene durante el alquiler. Se devuelve al entregar el vehículo en las mismas condiciones en que lo recibiste, descontando multas, daños o combustible faltante si los hubiera.</p>'],
            ['¿Qué formas de pago aceptan?', '<p>Puedes pagar al recibir el vehículo o por transferencia bancaria. Al reservar te indicamos las instrucciones.</p>'],
            ['¿Puedo cancelar o cambiar mi reserva?', '<p>Sí. Escríbenos con anticipación y reprogramamos tu reserva según la disponibilidad.</p>'],
            ['¿Puede conducir otra persona?', '<p>Solo las personas registradas en el contrato como conductores autorizados, con su licencia vigente. Puede aplicar un cargo por conductor adicional.</p>'],
        ];

        foreach ($faqs as [$question, $answer]) {
            Faq::query()->create(['question' => $question, 'answer' => $answer, 'category_id' => 0, 'status' => 'published']);
        }
    }
}
