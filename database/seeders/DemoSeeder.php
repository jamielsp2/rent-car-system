<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarExpense;
use App\Models\CarFuel;
use App\Models\CarMake;
use App\Models\CarTransmission;
use App\Models\CarType;
use App\Models\Customer;
use App\Models\Post;
use App\Models\Service;
use App\Models\Testimonial;
use App\Services\BookingCheckout;
use App\Support\LocalTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Made-up cars, customers, extras, bookings and blog posts, to try the system or
 * to run the tests. Never run it on a business's real database. Idempotent:
 * does nothing when there are cars already.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DefaultDataSeeder::class);

        if (Car::query()->exists()) {
            return;
        }

        $services = $this->services();
        $cars = $this->cars();
        $customers = $this->customers();

        $this->bookings($cars, $customers, $services);
        $this->expenses($cars);
        $this->posts();
        $this->testimonials();
    }

    /**
     * @return array<int, Service>
     */
    protected function services(): array
    {
        $rows = [
            ['Conductor adicional', 'Una segunda persona autorizada a conducir.', 8, 'per_day'],
            ['Silla de bebé', 'Asiento de seguridad para niños.', 5, 'per_day'],
            ['GPS', 'Navegador con mapas actualizados.', 6, 'per_day'],
            ['Entrega en el aeropuerto', 'Te llevamos el vehículo al aeropuerto.', 25, 'once'],
        ];

        return array_map(fn (array $row) => Service::query()->create([
            'name' => $row[0], 'description' => $row[1], 'price' => $row[2], 'price_type' => $row[3], 'status' => 'published',
        ]), $rows);
    }

    /**
     * @return array<int, Car>
     */
    protected function cars(): array
    {
        $rows = [
            ['Toyota Corolla 2022', 'Toyota', 'Corolla', 2022, 'Sedán', 'Automática', 'Gasolina', 5, 4, 45],
            ['Hyundai Tucson 2023', 'Hyundai', 'Tucson', 2023, 'SUV', 'Automática', 'Gasolina', 5, 5, 70],
            ['Kia Picanto 2021', 'Kia', 'Picanto', 2021, 'Hatchback', 'Manual', 'Gasolina', 5, 5, 30],
            ['Honda CR-V 2020', 'Honda', 'CR-V', 2020, 'SUV', 'Automática', 'Gasolina', 5, 5, 65],
            ['Toyota Hilux 2021', 'Toyota', 'Hilux', 2021, 'Pickup', 'Manual', 'Diésel', 5, 4, 85],
            ['Kia Carnival 2022', 'Kia', 'Carnival', 2022, 'Minivan', 'Automática', 'Diésel', 8, 5, 95],
            ['Nissan Kicks 2023', 'Nissan', 'Kicks', 2023, 'SUV', 'Automática', 'Gasolina', 5, 5, 55],
            ['Mazda 3 2021', 'Mazda', 'Mazda 3', 2021, 'Sedán', 'Automática', 'Gasolina', 5, 4, 50],
            // No rentals yet, so there are always free cars to book.
            ['Suzuki Swift 2022', 'Suzuki', 'Swift', 2022, 'Hatchback', 'Manual', 'Gasolina', 5, 5, 35],
            ['Toyota RAV4 2023', 'Toyota', 'RAV4', 2023, 'SUV', 'Automática', 'Híbrido', 5, 5, 80],
        ];

        return array_map(function (array $row, int $i): Car {
            [$name, $make, $model, $year, $type, $transmission, $fuel, $seats, $doors, $rate] = $row;

            $car = Car::query()->create([
                'name' => $name,
                'description' => "{$name}: cómodo, económico y listo para tu próximo viaje.",
                'content' => "<p>El <strong>{$name}</strong> es una excelente opción para moverte con comodidad. Entrega con el tanque lleno y asistencia durante todo el alquiler.</p>",
                'make_id' => CarMake::query()->where('name', $make)->value('id'),
                'model_name' => $model,
                'year' => $year,
                'vehicle_type_id' => CarType::query()->where('name', $type)->value('id'),
                'transmission_id' => CarTransmission::query()->where('name', $transmission)->value('id'),
                'fuel_type_id' => CarFuel::query()->where('name', $fuel)->value('id'),
                'number_of_seats' => $seats,
                'number_of_doors' => $doors,
                'rental_rate' => $rate,
                'deposit_amount' => 150,
                'min_rental_days' => 1,
                'status' => 'available',
                'license_plate' => 'A' . str_pad((string) (100000 + $i * 137), 6, '0', STR_PAD_LEFT),
                'is_featured' => $i < 3,
            ]);
            $car->syncSlug();

            return $car;
        }, $rows, array_keys($rows));
    }

    /**
     * @return array<int, Customer>
     */
    protected function customers(): array
    {
        $rows = [
            ['María Pérez', 'maria.perez@example.com', '8095550101'],
            ['Carlos Gómez', 'carlos.gomez@example.com', '8295550102'],
            ['Ana Rodríguez', 'ana.rodriguez@example.com', '8495550103'],
        ];

        return array_map(function (array $row): Customer {
            $customer = new Customer(['name' => $row[0], 'email' => $row[1], 'phone' => $row[2], 'status' => 'activated']);
            $customer->forceFill(['password' => Hash::make(str()->random(24))])->save();

            return $customer;
        }, $rows);
    }

    /**
     * Real bookings, made through the same service as the public checkout, then
     * moved along their life: completed, in progress, confirmed, pending, cancelled.
     */
    protected function bookings(array $cars, array $customers, array $services): void
    {
        $checkout = app(BookingCheckout::class);
        $today = LocalTime::today();

        $plan = [
            // [car, customer, start offset, days, status, payment status]
            [0, 0, -40, 3, BookingStatus::Completed, PaymentStatus::Completed],
            [1, 1, -25, 5, BookingStatus::Completed, PaymentStatus::Completed],
            [2, 2, -18, 2, BookingStatus::Completed, PaymentStatus::Completed],
            [3, 0, -12, 4, BookingStatus::Completed, PaymentStatus::Completed],
            [4, 1, -6, 3, BookingStatus::Completed, PaymentStatus::Completed],
            [5, 2, -1, 3, BookingStatus::Delivered, PaymentStatus::Completed],
            [6, 0, 3, 4, BookingStatus::Confirmed, PaymentStatus::Pending],
            [0, 1, 6, 3, BookingStatus::Pending, PaymentStatus::Pending],
            [1, 2, 10, 5, BookingStatus::Pending, PaymentStatus::Pending],
            [7, 0, 14, 2, BookingStatus::Pending, PaymentStatus::Pending],
            [2, 1, 20, 3, BookingStatus::Cancelled, PaymentStatus::Canceled],
        ];

        foreach ($plan as $i => [$carIndex, $customerIndex, $offset, $days, $status, $paymentStatus]) {
            $customer = $customers[$customerIndex];
            $start = $today->addDays($offset)->setTime(9, 0);

            $booking = $checkout->book(
                $cars[$carIndex],
                $start,
                $start->addDays($days),
                ['name' => $customer->name, 'email' => $customer->email, 'phone' => $customer->phone],
                $i % 2 ? PaymentMethod::BankTransfer : PaymentMethod::CashOnDelivery,
                $i % 3 === 0 ? [$services[0]->getKey(), $services[3]->getKey()] : [],
            );

            $booking->forceFill([
                'status' => $status,
                'completed_at' => $status === BookingStatus::Completed ? $start->addDays($days) : null,
            ])->save();

            $booking->payment?->forceFill(['status' => $paymentStatus])->save();
        }
    }

    protected function expenses(array $cars): void
    {
        $rows = [[0, 'Cambio de aceite', 45, -30], [1, 'Gomas nuevas', 320, -20], [3, 'Lavado y detallado', 25, -10]];

        foreach ($rows as [$carIndex, $name, $amount, $offset]) {
            CarExpense::query()->create([
                'car_id' => $cars[$carIndex]->getKey(),
                'name' => $name,
                'amount' => $amount,
                'date' => LocalTime::today()->addDays($offset),
            ]);
        }
    }

    protected function posts(): void
    {
        $rows = [
            ['Consejos para tu primer alquiler de vehículo', 'Qué revisar al recibir el auto, qué documentos llevar y cómo evitar sorpresas.'],
            ['Cómo elegir el vehículo ideal para tu viaje', 'Compacto, SUV o minivan: te ayudamos a decidir según tu grupo y tu ruta.'],
            ['Qué hacer en caso de avería o accidente', 'Los pasos a seguir y a quién llamar para que el viaje no se complique.'],
        ];

        foreach ($rows as [$title, $summary]) {
            $post = Post::query()->create([
                'name' => $title,
                'description' => $summary,
                'content' => "<p>{$summary}</p><p>Este es un artículo de ejemplo: edítalo o bórralo desde Blog en el panel.</p>",
                'status' => 'published',
            ]);
            $post->syncSlug();
        }
    }

    protected function testimonials(): void
    {
        $rows = [
            ['Laura Martínez', 'Cliente', 'Proceso rápido y el vehículo estaba impecable. Muy recomendado.'],
            ['Pedro Sánchez', 'Cliente', 'Reservé en línea en minutos y la entrega fue puntual.'],
            ['Sofía Díaz', 'Cliente', 'Excelente atención y precios claros, sin sorpresas.'],
        ];

        foreach ($rows as [$name, $company, $content]) {
            Testimonial::query()->create(['name' => $name, 'company' => $company, 'content' => $content, 'status' => 'published']);
        }
    }
}
