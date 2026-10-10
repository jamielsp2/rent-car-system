<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Filament\Clusters\Reservas\Pages\BookingCalendar;
use App\Filament\Clusters\Reservas\Pages\BookingDashboard;
use App\Filament\Clusters\Reservas\Pages\TodayHandovers;
use App\Filament\Clusters\Reservas\Widgets\BookingsOverview;
use App\Models\Booking;
use App\Models\BookingContract;
use App\Models\Car;
use App\Models\User;
use App\Services\BookingStats;
use App\Support\LocalTime;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class BookingsModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
        // A car without rentals, so the dates these tests book are always free.
        $this->car = Car::query()->published()->whereHas('slug')->whereDoesntHave('bookingCars')->with('slug')->firstOrFail();
        $this->car->update(['rental_rate' => 100, 'min_rental_days' => 1]);
    }

    protected function book(Carbon $from, Carbon $to): Booking
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'pickup_time' => '10:00',
            'name' => 'Cliente Panel',
            'email' => 'panel@example.com',
            'phone' => '8095551234',
            'payment_method' => 'cod',
            'terms' => '1',
        ]);

        return Booking::query()->latest('id')->firstOrFail();
    }

    public function test_cluster_pages_render(): void
    {
        $this->get(BookingDashboard::getUrl())->assertOk()->assertSee('Reservas')->assertSee('Últimos 30 días')->assertSee('Calendario')->assertSee('Hoy');
        $this->get(BookingCalendar::getUrl())->assertOk()->assertSee('booking-calendar', false);
        $this->get(TodayHandovers::getUrl())->assertOk()->assertSee('Entregar')->assertSee('Recibir');
    }

    public function test_summary_counts_bookings_by_pickup_date(): void
    {
        $stats = app(BookingStats::class);
        [$from, $to] = BookingStats::range(['period' => 'custom', 'from' => today()->addDays(90)->toDateString(), 'to' => today()->addDays(100)->toDateString()]);
        $before = $stats->summary($from, $to);

        $this->book(today()->addDays(92), today()->addDays(95)); // 3 days × $100 + 18% ITBIS

        $after = $stats->summary($from, $to);
        $this->assertSame($before['bookings'] + 1, $after['bookings']);
        $this->assertEqualsWithDelta($before['revenue'] + 354, $after['revenue'], 0.01);
        $this->assertGreaterThanOrEqual(1, $stats->byStatus($from, $to)['Pendiente']);

        $trend = $stats->trend($from, $to);
        $this->assertCount(11, $trend['labels']); // one point per day for short periods
        $this->assertSame($after['bookings'], array_sum($trend['bookings']));
    }

    public function test_summary_widgets_render_for_every_period(): void
    {
        $this->book(today()->addDays(2), today()->addDays(4));

        foreach (array_keys(BookingStats::PRESETS) as $period) {
            foreach ((new BookingDashboard)->getWidgets() as $widget) {
                Livewire::test($widget, ['pageFilters' => ['period' => $period]])->assertOk();
            }
        }

        // Money lives in the reports page, not here.
        Livewire::test(BookingsOverview::class, ['pageFilters' => ['period' => '30d']])
            ->assertSee('Por confirmar')
            ->assertDontSee('Ingresos');
    }

    public function test_dashboard_filters_change_the_period(): void
    {
        Livewire::test(BookingDashboard::class)
            ->callAction('period_12m')
            ->assertSet('filters.period', '12m')
            ->callAction('period_custom', ['from' => '2026-01-01', 'to' => '2026-01-31'])
            ->assertSet('filters.period', 'custom')
            ->assertSet('filters.from', '2026-01-01');

        [$from, $to] = BookingStats::range(['period' => '12m']);
        $this->assertCount(13, app(BookingStats::class)->trend($from, $to)['labels']); // monthly points
    }

    public function test_calendar_feed_returns_bookings_in_range(): void
    {
        $booking = $this->book(today()->addDays(5), today()->addDays(8));

        $events = $this->getJson(route('admin.bookings.calendar.events', [
            'start' => today()->toDateString(),
            'end' => today()->addMonth()->toDateString(),
            'car' => $this->car->id,
        ]))->assertOk()->json();

        $event = collect($events)->firstWhere('id', $booking->id);
        $this->assertNotNull($event);
        $this->assertStringContainsString('Cliente Panel', $event['title']);
        $this->assertSame('#f59f00', $event['color']);
    }

    public function test_today_lists_deliveries_returns_and_overdue(): void
    {
        // The page works on Dominican time; UTC is already tomorrow from 8 p.m.
        $today = Carbon::parse(LocalTime::today()->toDateString());
        $delivery = $this->book($today, $today->copy()->addDays(3));

        $overdue = $this->book($today->copy()->addDays(20), $today->copy()->addDays(22));
        $overdue->update(['status' => BookingStatus::Delivered]);
        $overdue->rental->update(['rental_start_date' => now()->subDays(5), 'rental_end_date' => now()->subDays(2)]);

        Livewire::test(TodayHandovers::class)
            ->assertSee($delivery->booking_number)
            ->assertSee('Firmar contrato')
            ->assertSee('Devoluciones atrasadas')
            ->assertSee($overdue->booking_number);

        // Without a signed contract the vehicle can't be marked as delivered.
        Livewire::test(TodayHandovers::class)->call('markDelivered', $delivery->id);
        $this->assertSame(BookingStatus::Pending, $delivery->fresh()->status);

        // Only vehicles that went out are expected back.
        Livewire::test(TodayHandovers::class)->call('shiftDay', 3)->assertDontSee($delivery->booking_number);
        $delivery->update(['status' => BookingStatus::Delivered]);
        Livewire::test(TodayHandovers::class)->call('shiftDay', 3)->assertSee($delivery->booking_number);
    }

    public function test_today_shows_vehicles_handed_over_today_even_if_scheduled_for_another_day(): void
    {
        $today = Carbon::parse(LocalTime::today()->toDateString());
        $early = $this->book($today->copy()->addDays(2), $today->copy()->addDays(5));
        $early->update(['status' => BookingStatus::Delivered]);
        BookingContract::query()->create(['booking_id' => $early->id, 'signed_at' => now()]);

        Livewire::test(TodayHandovers::class)
            ->assertSee($early->booking_number)
            ->assertSee('Ya salieron');
    }
}
