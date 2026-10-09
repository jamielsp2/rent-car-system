<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Currency;
use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Creates a booking from the public website: the booking, the rented car, a
 * pending payment and its invoice.
 */
class BookingCheckout
{
    // Polymorphic types stored in the invoices and bookings tables.
    protected const BOOKING_TYPE = Booking::class;

    protected const CUSTOMER_TYPE = Customer::class;

    public function __construct(protected RentalQuote $quotes)
    {
    }

    /**
     * @param  array{name: string, email: string, phone: string, note?: ?string}  $customer
     * @param  int[]  $serviceIds
     */
    public function book(Car $car, CarbonInterface $from, CarbonInterface $to, array $customer, PaymentMethod $method, array $serviceIds = []): Booking
    {
        $quote = $this->quotes->quote($car, $from, $to, $serviceIds);

        if (! $quote['meets_min_days']) {
            throw ValidationException::withMessages(['to' => "Este vehículo se renta por un mínimo de {$quote['min_days']} días."]);
        }

        return DB::transaction(function () use ($car, $from, $to, $customer, $method, $quote): Booking {
            // Lock the car row so two people can't take the same dates at once.
            Car::query()->whereKey($car->getKey())->lockForUpdate()->first();

            if (! Car::query()->whereKey($car->getKey())->availableBetween($from, $to)->exists()) {
                throw ValidationException::withMessages(['from' => 'Este vehículo ya no está disponible en esas fechas.']);
            }

            $currency = Currency::default();
            $account = Customer::query()->where('email', $customer['email'])->first();

            $booking = new Booking();
            $booking->forceFill([
                'booking_number' => $this->nextBookingNumber(),
                'transaction_id' => Str::upper(Str::random(32)),
                'customer_id' => $account?->getKey(),
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'note' => $customer['note'] ?? null,
                'sub_total' => $quote['subtotal'],
                'tax_amount' => $quote['tax_total'],
                'coupon_amount' => 0,
                'credit_amount' => 0,
                'amount' => $quote['total'],
                'deposit_amount' => $quote['deposit'],
                'currency_id' => $currency?->getKey(),
                'status' => BookingStatus::Pending,
            ])->save();

            $booking->rental()->create([
                'car_id' => $car->getKey(),
                'car_name' => $car->name,
                'car_image' => collect($car->images)->first(),
                'price' => $quote['rental'],
                'currency_id' => $currency?->getKey(),
                'rental_start_date' => $from,
                'rental_end_date' => $to,
            ]);

            $paymentId = DB::table('payments')->insertGetId([
                'currency' => $currency?->title ?? 'USD',
                'user_id' => 0,
                'charge_id' => Str::upper(Str::random(10)),
                'payment_channel' => $method->value,
                'amount' => $quote['total'],
                'payment_fee' => 0,
                'order_id' => $booking->getKey(),
                'status' => PaymentStatus::Pending->value,
                'payment_type' => 'confirm',
                'customer_id' => $account?->getKey(),
                'customer_type' => self::CUSTOMER_TYPE,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $booking->forceFill(['payment_id' => $paymentId])->save();

            $invoiceId = DB::table('cr_invoices')->insertGetId([
                'customer_id' => $account?->getKey() ?? 0,
                'customer_name' => $customer['name'],
                'customer_email' => $customer['email'],
                'customer_phone' => $customer['phone'],
                'payment_id' => $paymentId,
                'currency_id' => $currency?->getKey(),
                'reference_type' => self::BOOKING_TYPE,
                'reference_id' => $booking->getKey(),
                'code' => 'INV-' . Str::random(8),
                'sub_total' => $quote['subtotal'],
                'tax_amount' => $quote['tax_total'],
                'discount_amount' => 0,
                'amount' => $quote['total'],
                'status' => InvoiceStatus::Pending->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('cr_invoices')->where('id', $invoiceId)->update(['code' => 'INV-' . $invoiceId]);

            $lines = [['name' => $car->name, 'description' => $from->format('d/m/Y') . ' - ' . $to->format('d/m/Y'), 'amount' => $quote['rental']]];

            foreach ($quote['services'] as $service) {
                DB::table('cr_booking_service')->insert(['booking_id' => $booking->getKey(), 'service_id' => $service['id']]);
                $lines[] = ['name' => $service['name'], 'description' => 'Servicio adicional', 'amount' => $service['amount']];
            }

            DB::table('cr_invoice_items')->insert(array_map(fn (array $line) => [
                'invoice_id' => $invoiceId,
                'name' => $line['name'],
                'description' => $line['description'],
                'qty' => 1,
                'sub_total' => $line['amount'],
                'tax_amount' => 0,
                'discount_amount' => 0,
                'amount' => $line['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ], $lines));

            return $booking->fresh(['rental.car', 'payment', 'services']);
        });
    }

    /**
     * Next number of the sequence (#1000001, #1000002, ...).
     */
    protected function nextBookingNumber(): string
    {
        $last = (int) Booking::query()
            ->lockForUpdate()
            ->selectRaw("MAX(CAST(REPLACE(booking_number, '#', '') AS UNSIGNED)) AS n")
            ->value('n');

        return '#' . max($last + 1, 1000001);
    }
}
