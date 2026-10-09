<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Faq;
use App\Models\Post;
use App\Support\Html;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
        $this->car->update(['min_rental_days' => 1]);
    }

    protected function bookingData(array $overrides = []): array
    {
        return array_merge([
            'from' => now()->addDays(40)->toDateString(),
            'to' => now()->addDays(43)->toDateString(),
            'pickup_time' => '09:00',
            'name' => 'Cliente Web',
            'email' => 'cliente.web@example.com',
            'phone' => '8095550000',
            'payment_method' => PaymentMethod::CashOnDelivery->value,
            'terms' => '1',
        ], $overrides);
    }

    public function test_home_and_catalog_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Vehículos populares')->assertSee($this->car->name);
        $this->get('/cars')->assertOk()->assertSee('vehículos encontrados');
        $this->get('/cars?sort=price_asc&type[]=' . $this->car->vehicle_type_id)->assertOk()->assertSee($this->car->name);
    }

    public function test_home_search_with_all_types_shows_the_catalog(): void
    {
        $from = now()->addDays(40)->toDateString();
        $to = now()->addDays(43)->toDateString();

        // "Todos" sends an empty type[]: it must search, not bounce back home.
        $this->get("/cars?from={$from}&to={$to}&type[]=&make[]={$this->car->make_id}")
            ->assertOk()
            ->assertSee('Vehículos libres del')
            ->assertSee($this->car->name)
            ->assertSee("from={$from}", false);

        $this->get('/cars?from=2020-01-01&to=2020-01-05')
            ->assertOk()
            ->assertSee('no puede ser anterior a hoy');
    }

    public function test_search_hides_cars_booked_in_those_dates(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData());

        $this->get('/cars?from=' . now()->addDays(41)->toDateString() . '&to=' . now()->addDays(42)->toDateString())
            ->assertOk()
            ->assertDontSee($this->car->public_url, false);
    }

    public function test_car_calendar_marks_booked_nights(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData());

        $start = now()->addDays(40)->toDateString();
        $lastNight = now()->addDays(42)->toDateString(); // returned on day 43, which stays free

        $this->get("/cars/{$this->car->slug->key}")
            ->assertOk()
            ->assertSee('rental-calendar', false)
            ->assertSee('"from":"' . $start . '","to":"' . $lastNight . '"', false);
    }

    public function test_car_page_keeps_the_current_url_and_quotes_the_price(): void
    {
        $from = now()->addDays(40)->toDateString();
        $to = now()->addDays(43)->toDateString();

        $this->get("/cars/{$this->car->slug->key}?from={$from}&to={$to}")
            ->assertOk()
            ->assertSee($this->car->name)
            ->assertSee('3 días')
            ->assertSee('Reservar ahora');

        $this->get('/cars/no-existe')->assertNotFound();
    }

    public function test_checkout_creates_booking_payment_and_invoice(): void
    {
        $lastNumber = (int) DB::table('cr_bookings')->selectRaw("MAX(CAST(REPLACE(booking_number, '#', '') AS UNSIGNED)) n")->value('n');

        $response = $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData());

        $booking = Booking::query()->with(['rental', 'payment', 'invoice'])->latest('id')->firstOrFail();
        $response->assertRedirect(route('site.booking', $booking->transaction_id));

        $this->assertSame('#' . ($lastNumber + 1), $booking->booking_number);
        $this->assertSame(BookingStatus::Pending, $booking->status);
        $this->assertSame($this->car->getKey(), $booking->rental->car_id);
        $this->assertSame('09:00', $booking->rental->rental_start_date->format('H:i'));
        $this->assertEqualsWithDelta($this->car->rental_rate * 3, $booking->sub_total, 0.01);
        $this->assertSame(PaymentStatus::Pending, $booking->payment->status);
        $this->assertSame(PaymentMethod::CashOnDelivery, $booking->payment->payment_channel);
        $this->assertEqualsWithDelta($booking->amount, $booking->payment->amount, 0.01);
        $this->assertSame(InvoiceStatus::Pending, $booking->invoice->status);
        $this->assertSame('INV-' . $booking->invoice->id, $booking->invoice->code);

        $this->get(route('site.booking', $booking->transaction_id))->assertOk()->assertSee($booking->booking_number);
    }

    public function test_overlapping_dates_are_refused(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData());
        $count = Booking::count();

        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData([
            'from' => now()->addDays(42)->toDateString(),
            'to' => now()->addDays(45)->toDateString(),
        ]))->assertSessionHasErrors('from');

        $this->assertSame($count, Booking::count());
    }

    public function test_a_car_can_go_out_again_on_its_return_day(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData()); // days 40 → 43

        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData([
            'from' => now()->addDays(43)->toDateString(),
            'to' => now()->addDays(45)->toDateString(),
            'pickup_time' => '08:00',
            'email' => 'siguiente@example.com',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::query()->whereHas('rental', fn ($q) => $q->where('car_id', $this->car->id)->whereDate('rental_start_date', '>=', now()->addDays(40)))->count());
    }

    public function test_minimum_rental_days_are_enforced(): void
    {
        $this->car->update(['min_rental_days' => 5]);

        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData())
            ->assertSessionHasErrors('to');
    }

    public function test_content_pages_render_on_the_current_paths(): void
    {
        $this->get('/about-us')->assertOk()->assertSee('¿Por qué elegirnos?')->assertSee('Precios transparentes');
        $this->get('/faqs')->assertOk()->assertSee(Faq::query()->published()->value('question'));
        $this->get('/blog')->assertOk();

        $post = Post::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
        $views = $post->views;

        $this->get('/news/' . $post->slug->key)->assertOk()->assertSee($post->name);
        $this->assertSame($views + 1, $post->fresh()->views);
        $this->get('/news/no-existe')->assertNotFound();
    }

    public function test_editor_html_is_cleaned(): void
    {
        $html = Html::clean('<p onclick="x()">Hola <a href="javascript:alert(1)">link</a></p><script>alert(1)</script><img src="a.png" onerror="x()">');

        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringContainsString('Hola', $html);
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post(route('site.checkout.store', $this->car->slug->key), $this->bookingData(['terms' => null]))
            ->assertSessionHasErrors('terms');
    }
}
