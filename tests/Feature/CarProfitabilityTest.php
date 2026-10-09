<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Filament\Resources\Cars\CarResource;
use App\Filament\Resources\Cars\Pages\EditCar;
use App\Filament\Resources\Cars\Pages\ListCars;
use App\Filament\Resources\Cars\RelationManagers\ExpensesRelationManager;
use App\Models\Booking;
use App\Models\Car;
use App\Models\CarExpense;
use App\Models\User;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CarProfitabilityTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected int $year = 2031; // a year with no real data

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->firstOrFail();
        $this->actingAs(User::query()->firstOrFail());
    }

    protected function rental(string $from, string $to, float $price, BookingStatus $status): void
    {
        $booking = new Booking();
        $booking->forceFill([
            'booking_number' => '#T' . random_int(100000, 999999),
            'customer_name' => 'Prueba',
            'amount' => $price,
            'sub_total' => $price,
            'tax_amount' => 0,
            'coupon_amount' => 0,
            'status' => $status,
        ])->save();

        DB::table('cr_booking_cars')->insert([
            'booking_id' => $booking->id,
            'car_id' => $this->car->id,
            'car_name' => $this->car->name,
            'price' => $price,
            'currency_id' => 1,
            'rental_start_date' => Carbon::parse($from),
            'rental_end_date' => Carbon::parse($to),
        ]);
    }

    protected function stats(): Car
    {
        return Car::query()->whereKey($this->car->id)->withYearStats($this->year)->firstOrFail();
    }

    public function test_profit_is_rental_income_minus_expenses(): void
    {
        $this->rental("{$this->year}-03-01 09:00", "{$this->year}-03-05 09:00", 400, BookingStatus::Completed);
        $this->rental("{$this->year}-05-10 09:00", "{$this->year}-05-12 09:00", 200, BookingStatus::Delivered);
        $this->rental("{$this->year}-06-01 09:00", "{$this->year}-06-03 09:00", 999, BookingStatus::Pending);   // not counted
        $this->rental("{$this->year}-07-01 09:00", "{$this->year}-07-03 09:00", 999, BookingStatus::Cancelled); // not counted
        CarExpense::create(['car_id' => $this->car->id, 'name' => 'Gomas', 'amount' => 150, 'date' => "{$this->year}-04-02"]);
        CarExpense::create(['car_id' => $this->car->id, 'name' => 'Otro año', 'amount' => 999, 'date' => ($this->year - 1) . '-12-31']);

        $car = $this->stats();
        $this->assertEqualsWithDelta(600, $car->year_income, 0.01);
        $this->assertEqualsWithDelta(150, $car->year_expenses, 0.01);
        $this->assertEqualsWithDelta(450, $car->year_profit, 0.01);
        $this->assertEquals(6, (int) $car->year_rented_days);
    }

    public function test_rentals_crossing_new_year_are_split_by_days(): void
    {
        // 4 days: 2 in the previous year, 2 in this one → half the price.
        $this->rental(($this->year - 1) . '-12-30 09:00', "{$this->year}-01-03 09:00", 400, BookingStatus::Completed);

        $car = $this->stats();
        $this->assertEqualsWithDelta(200, $car->year_income, 0.01);
        $this->assertEquals(2, (int) $car->year_rented_days);
    }

    public function test_the_table_can_show_another_year(): void
    {
        $this->rental('2025-03-01 09:00', '2025-03-05 09:00', 400, BookingStatus::Completed);

        Livewire::test(ListCars::class)
            ->filterTable('year', '2025')
            ->assertSee('Ingresos 2025')
            ->assertTableColumnStateSet('year_income', 400, $this->car);
    }

    public function test_columns_and_expenses_show_in_the_admin(): void
    {
        $this->get(CarResource::getUrl())->assertOk()->assertSee('Rentabilidad ' . LocalTime::now()->year)->assertSee('Ingresos ' . LocalTime::now()->year);
        $this->get(CarResource::getUrl('view', ['record' => $this->car]))->assertOk()->assertSee('Rentabilidad ' . now()->year);

        Livewire::test(ExpensesRelationManager::class, ['ownerRecord' => $this->car, 'pageClass' => EditCar::class])
            ->callTableAction('create', data: ['name' => 'Cambio de aceite', 'amount' => 45, 'date' => now()->toDateString()])
            ->assertHasNoTableActionErrors();

        $this->assertTrue(CarExpense::query()->where('car_id', $this->car->id)->where('name', 'Cambio de aceite')->exists());
    }
}
