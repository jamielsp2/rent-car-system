<?php

namespace Tests\Feature;

use App\Filament\Resources\Currencies\Pages\ManageCurrencies;
use App\Models\Car;
use App\Models\Currency;
use App\Models\Setting;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
        $this->car->update(['rental_rate' => 100]);
        Currency::query()->where('title', 'DOP')->update(['exchange_rate' => 60, 'decimals' => 0]);
        Setting::put(['site_default_currency' => 'USD']);
    }

    public function test_prices_show_in_usd_by_default(): void
    {
        $this->get("/cars/{$this->car->slug->key}")->assertOk()->assertSee('$100');
    }

    public function test_visitors_can_switch_currency(): void
    {
        $this->get(route('site.currency', 'DOP'))->assertRedirect();

        $this->get("/cars/{$this->car->slug->key}")
            ->assertOk()
            ->assertSee('RD$6,000');

        $this->get(route('site.currency', 'EUR'));
        $this->get('/cars')->assertOk()->assertSee('€');

        // Unknown codes are ignored.
        $this->get(route('site.currency', 'XYZ'));
        $this->assertSame('EUR', session('currency'));
    }

    public function test_bookings_are_still_stored_in_the_base_currency(): void
    {
        $this->get(route('site.currency', 'DOP'));

        $this->post(route('site.checkout.store', $this->car->slug->key), [
            'from' => now()->addDays(60)->toDateString(),
            'to' => now()->addDays(62)->toDateString(),
            'pickup_time' => '09:00',
            'name' => 'Cliente DOP',
            'email' => 'dop@example.com',
            'phone' => '8095550000',
            'payment_method' => 'cod',
            'terms' => '1',
        ]);

        $booking = \App\Models\Booking::query()->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(200, $booking->sub_total, 0.01);
    }

    public function test_price_filter_uses_the_visitor_currency(): void
    {
        $this->get(route('site.currency', 'DOP'));

        // 6,000 DOP = 100 USD, so the car is inside a 5,000–7,000 DOP range...
        $this->get('/cars?min_price=5000&max_price=7000')->assertSee($this->car->name);
        // ...and outside 100–200 DOP.
        $this->get('/cars?min_price=100&max_price=200')->assertDontSee($this->car->name);
    }

    public function test_admin_can_adjust_rates_but_not_the_base(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $dop = Currency::query()->where('title', 'DOP')->firstOrFail();

        Livewire::test(ManageCurrencies::class)
            ->callAction(TestAction::make('edit')->table($dop), ['exchange_rate' => 61.5])
            ->assertHasNoFormErrors();

        $this->assertSame(61.5, $dop->fresh()->exchange_rate);

        Livewire::test(ManageCurrencies::class)
            ->assertActionHidden(TestAction::make('delete')->table(Currency::default()));
    }
}
