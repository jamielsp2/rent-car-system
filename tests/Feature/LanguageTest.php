<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Setting;
use App\Support\Lang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
    }

    public function test_spanish_stays_at_the_root_and_english_lives_under_en(): void
    {
        $this->get('/')->assertOk()->assertSee('<html lang="es">', false)->assertSee('Nuestros vehículos');

        $this->get('/en')->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Our vehicles')
            ->assertSee('Popular vehicles')
            ->assertDontSee('Nuestros vehículos');
    }

    public function test_links_stay_in_the_language_being_read(): void
    {
        $this->get('/en')->assertSee(route('en.site.cars'), false);
        $this->get('/')->assertSee(route('site.cars'), false)->assertDontSee('/en/cars');

        $this->get('/en/cars/' . $this->car->slug->key)->assertOk()
            ->assertSee('Book this vehicle')
            ->assertSee(route('en.site.car.quote', $this->car->slug->key), false);
    }

    public function test_the_toggle_leads_to_the_same_page_in_the_other_language(): void
    {
        $key = $this->car->slug->key;

        $this->get("/cars/{$key}")->assertSee('href="' . route('en.site.car', $key) . '"', false);
        $this->get("/en/cars/{$key}")->assertSee('href="' . route('site.car', $key) . '"', false);
        $this->get('/en/faqs?x=1')->assertSee('href="' . route('site.faqs') . '?x=1"', false);
    }

    public function test_a_language_the_site_disabled_is_not_served_and_hides_the_toggle(): void
    {
        Setting::put(['site_locales' => 'es']);

        $this->get('/en')->assertNotFound();
        $this->get('/')->assertOk()->assertDontSee('hreflang="en"', false);
        $this->assertSame(['es'], Lang::enabled());
    }

    public function test_settings_fall_back_from_english_to_spanish_to_the_default(): void
    {
        $this->get('/en')->assertSee('Your ideal vehicle is waiting');

        Setting::put(['site_hero_title' => 'Renta de autos Premium']);
        $this->get('/en')->assertSee('Renta de autos Premium');

        Setting::put(['site_hero_title_en' => 'Premium car rental']);
        $this->get('/en')->assertSee('Premium car rental');
        $this->get('/')->assertSee('Renta de autos Premium');
    }

    public function test_the_booking_form_validates_in_english(): void
    {
        $this->post('/en/reservar/' . $this->car->slug->key, [])->assertSessionHasErrors(['name', 'terms']);
    }
}
