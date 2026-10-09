<?php

namespace Tests\Feature;

use App\Enums\ServicePriceType;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Service;
use App\Models\User;
use App\Services\RentalQuote;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected Service $driver;

    protected Service $delivery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
        $this->car->update(['rental_rate' => 100, 'min_rental_days' => 1, 'deposit_amount' => 0]);
        $this->driver = Service::create(['name' => 'Chofer', 'price' => 50, 'price_type' => ServicePriceType::PerDay, 'status' => 'published']);
        $this->delivery = Service::create(['name' => 'Entrega en aeropuerto', 'price' => 30, 'price_type' => ServicePriceType::Once, 'status' => 'published']);
    }

    protected function dates(): array
    {
        return ['from' => now()->addDays(70)->toDateString(), 'to' => now()->addDays(73)->toDateString()];
    }

    public function test_extras_are_priced_per_day_or_once_and_taxed(): void
    {
        $quote = app(RentalQuote::class)->quote(
            $this->car,
            Carbon::parse($this->dates()['from']),
            Carbon::parse($this->dates()['to']),
            [$this->driver->id, $this->delivery->id],
        );

        $this->assertSame(300.0, $quote['rental']);
        $this->assertSame(180.0, $quote['services_total']); // 50 × 3 + 30
        $this->assertSame(480.0, $quote['subtotal']);
        $this->assertEqualsWithDelta(480 * 0.18, $quote['tax_total'], 0.01); // ITBIS on rental + extras
    }

    public function test_hidden_extras_are_ignored(): void
    {
        $this->driver->update(['status' => 'draft']);

        $quote = app(RentalQuote::class)->quote($this->car, Carbon::parse($this->dates()['from']), Carbon::parse($this->dates()['to']), [$this->driver->id]);

        $this->assertSame([], $quote['services']);
    }

    public function test_price_box_refreshes_with_dates_and_extras(): void
    {
        $this->get(route('site.car.quote', ['key' => $this->car->slug->key] + $this->dates() + ['services' => [$this->delivery->id]]))
            ->assertOk()
            ->assertSee('Entrega en aeropuerto')
            ->assertSee('Reservar ahora')
            ->assertSee('services', false);

        $this->get(route('site.car.quote', ['key' => $this->car->slug->key, 'from' => LocalTime::today()->subDay()->toDateString(), 'to' => LocalTime::today()->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('no puede ser anterior a hoy')
            ->assertDontSee('Reservar ahora');

        $this->get("/cars/{$this->car->slug->key}")->assertOk()->assertSee('Agrega extras')->assertSee('Chofer');
    }

    public function test_booking_keeps_the_chosen_extras(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->dates() + [
            'pickup_time' => '10:00',
            'name' => 'Cliente Extras',
            'email' => 'extras@example.com',
            'phone' => '8095550000',
            'payment_method' => 'cod',
            'services' => [$this->driver->id, $this->delivery->id],
            'terms' => '1',
        ])->assertRedirect();

        $booking = Booking::query()->with(['services', 'rental', 'invoice.items'])->latest('id')->firstOrFail();

        $this->assertEqualsCanonicalizing([$this->driver->id, $this->delivery->id], $booking->services->pluck('id')->all());
        $this->assertEqualsWithDelta(480, $booking->sub_total, 0.01);
        $this->assertEqualsWithDelta(300, $booking->rental->price, 0.01);
        $this->assertCount(3, $booking->invoice->items);
    }

    public function test_admin_can_manage_extras(): void
    {
        $this->actingAs(User::query()->firstOrFail());

        Livewire::test(ManageServices::class)
            ->callAction('create', ['name' => 'Silla para niños', 'price' => 10, 'price_type' => 'per_day', 'status' => 'published'])
            ->assertHasNoFormErrors();

        $this->assertTrue(DB::table('cr_services')->where('name', 'Silla para niños')->where('price_type', 'per_day')->exists());
    }
}
