<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCreateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_staff_can_open_create_pages(): void
    {
        $this->get(CustomerResource::getUrl('create'))->assertOk();
        $this->get(BookingResource::getUrl('create'))->assertOk();
    }

    public function test_staff_can_create_a_customer(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Cliente Admin',
                'email' => 'admin-cliente@example.com',
                'phone' => '8095550000',
                'status' => 'activated',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cr_customers', ['email' => 'admin-cliente@example.com', 'name' => 'Cliente Admin']);
    }

    public function test_staff_can_create_a_booking_for_a_customer(): void
    {
        $car = Car::query()->published()->whereHas('slug')->whereDoesntHave('bookingCars')->firstOrFail();
        $car->update(['rental_rate' => 100, 'min_rental_days' => 1]);
        $customer = Customer::query()->create(['name' => 'Reserva Admin', 'email' => 'reserva-admin@example.com', 'phone' => '8095551111', 'status' => 'activated']);

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'customer_id' => $customer->getKey(),
                'car_id' => $car->getKey(),
                'payment_method' => 'cod',
                'from' => Carbon::now()->addDays(10)->setTime(10, 0)->toDateTimeString(),
                'to' => Carbon::now()->addDays(13)->setTime(10, 0)->toDateTimeString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::query()->latest('id')->firstOrFail();
        $this->assertSame($customer->getKey(), $booking->customer_id);
        $this->assertSame('reserva-admin@example.com', $booking->customer_email);
        $this->assertSame(300.0, (float) $booking->rental->price);
    }

    public function test_booking_on_taken_dates_is_refused(): void
    {
        $car = Car::query()->published()->whereHas('slug')->whereDoesntHave('bookingCars')->firstOrFail();
        $car->update(['rental_rate' => 100, 'min_rental_days' => 1]);
        $customer = Customer::query()->create(['name' => 'Ocupado', 'email' => 'ocupado@example.com', 'phone' => '8095552222', 'status' => 'activated']);

        $data = [
            'customer_id' => $customer->getKey(),
            'car_id' => $car->getKey(),
            'payment_method' => 'cod',
            'from' => Carbon::now()->addDays(20)->setTime(10, 0)->toDateTimeString(),
            'to' => Carbon::now()->addDays(22)->setTime(10, 0)->toDateTimeString(),
        ];

        Livewire::test(CreateBooking::class)->fillForm($data)->call('create');
        $count = Booking::query()->count();

        Livewire::test(CreateBooking::class)->fillForm($data)->call('create');

        $this->assertSame($count, Booking::query()->count());
    }
}
