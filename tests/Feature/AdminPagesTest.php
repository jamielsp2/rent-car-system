<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\CarStatus;
use App\Filament\Resources\Cars\Pages\CreateCar;
use App\Filament\Resources\Cars\Pages\EditCar;
use App\Enums\PaymentStatus;
use App\Filament\Pages\ContractSettings;
use App\Filament\Pages\SiteSettings;
use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Models\Post;
use App\Support\SiteContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use App\Services\ContractSettings as Settings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    public function test_guests_are_sent_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/cars')->assertRedirect('/admin/login');
    }

    public function test_car_pages_render(): void
    {
        $car = Car::query()->firstOrFail();

        $this->get('/admin/cars')->assertOk();
        $this->get("/admin/cars/{$car->getKey()}")->assertOk()->assertSee($car->name);
        $this->get("/admin/cars/{$car->getKey()}/edit")->assertOk();
        $this->get('/admin/cars/create')->assertOk();
    }

    public function test_a_car_can_be_added_and_shows_on_the_site(): void
    {
        Livewire::test(CreateCar::class)
            ->fillForm([
                'name' => 'Kia Sportage 2024',
                'rental_rate' => 65,
                'min_rental_days' => 2,
                'status' => CarStatus::Available->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $car = Car::query()->where('name', 'Kia Sportage 2024')->firstOrFail();
        $this->assertSame('approved', $car->moderation_status);
        $this->assertSame('kia-sportage-2024', $car->slug->key);
        $this->get('/cars/kia-sportage-2024')->assertOk()->assertSee('Kia Sportage 2024');
    }

    public function test_editing_a_car_keeps_its_photos_in_the_site_format(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('cars/a.jpg', 'a');
        Storage::disk('media')->put('cars/b.jpg', 'b');

        $car = Car::query()->firstOrFail();
        $car->update(['images' => [1 => 'cars\\a.jpg', 2 => 'cars/b.jpg']]);

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm(['rental_rate' => 99])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            '{"1":"cars\/a.jpg","2":"cars\/b.jpg"}',
            DB::table('cr_cars')->where('id', $car->getKey())->value('images'),
        );
    }

    public function test_the_english_version_of_a_car_is_saved_from_the_admin_form(): void
    {
        $car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();

        Livewire::test(EditCar::class, ['record' => $car->getKey()])
            ->fillForm([
                'translations.en.name' => 'Saved English Name',
                'translations.en.description' => 'Saved English summary.',
                'translations.en.content' => '<p>Saved English body</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $saved = Car::query()->findOrFail($car->getKey());
        $this->assertSame('Saved English Name', $saved->translation('name', 'en'));
        $this->assertSame($car->name, $saved->name);
        $this->get('/en/cars/' . $car->slug->key)->assertSee('Saved English Name')->assertSee('Saved English summary.');
    }

    public function test_customer_pages_render(): void
    {
        $customer = Customer::query()->firstOrFail();

        $this->get('/admin/customers')->assertOk();
        $this->get("/admin/customers/{$customer->getKey()}")->assertOk()->assertSee($customer->email);
        $this->get("/admin/customers/{$customer->getKey()}/edit")->assertOk();
    }

    public function test_booking_page_saves_through_the_business_rules(): void
    {
        $booking = Booking::query()->whereHas('payment')->where('status', BookingStatus::Pending)->firstOrFail();
        $booking->contract()->delete();

        // Delivered without a signed contract is refused and nothing changes.
        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['status' => BookingStatus::Delivered->value])
            ->call('save');
        $this->assertSame(BookingStatus::Pending, $booking->fresh()->status);

        // A normal change saves status and payment together.
        Livewire::test(EditBooking::class, ['record' => $booking->getKey()])
            ->fillForm(['status' => BookingStatus::Confirmed->value, 'payment_status' => PaymentStatus::Completed->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $booking->refresh();
        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertSame(PaymentStatus::Completed, $booking->payment->status);
    }

    public function test_site_settings_change_logo_and_hero(): void
    {
        Livewire::test(SiteSettings::class)
            ->fillForm([
                'site_hero_title' => 'Renta tu carro hoy',
                'site_hero_badge' => 'Nuevo',
                'site_hero_subtitle' => 'Texto de prueba',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/')->assertOk()->assertSee('Renta tu carro hoy')->assertSee('Texto de prueba');
        $this->assertSame('Renta tu carro hoy', SiteContent::hero()['title']);
    }

    public function test_site_settings_save_the_english_texts_and_the_enabled_languages(): void
    {
        Livewire::test(SiteSettings::class)
            ->fillForm([
                'site_hero_title' => 'Renta tu carro hoy',
                'site_hero_title_en' => 'Rent your car today',
                'site_locales' => ['es', 'en'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->get('/en')->assertOk()->assertSee('Rent your car today');
        $this->get('/')->assertSee('Renta tu carro hoy')->assertDontSee('Rent your car today');

        // Spanish can never be switched off, and a site can be left in one language only.
        Livewire::test(SiteSettings::class)->fillForm(['site_locales' => []])->call('save')->assertHasNoFormErrors();
        $this->assertSame(['es'], \App\Support\Lang::enabled());
        $this->get('/en')->assertNotFound();
    }

    public function test_blog_posts_can_be_written_from_the_admin(): void
    {
        Livewire::test(CreatePost::class)
            ->fillForm([
                'name' => 'Playas para visitar en carro',
                'description' => 'Las mejores playas',
                'content' => '<p>Contenido del artículo</p>',
                'status' => 'published',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('name', 'Playas para visitar en carro')->firstOrFail();
        $this->assertSame('playas-para-visitar-en-carro', $post->slug->key);
        $this->assertSame(User::class, $post->author_type);
        $this->assertTrue(DB::table('post_categories')->where('post_id', $post->id)->where('category_id', Post::BLOG_CATEGORY_ID)->exists());

        $this->get('/news/playas-para-visitar-en-carro')->assertOk()->assertSee('Contenido del artículo');
        $this->get('/blog')->assertSee('Playas para visitar en carro');

        // A second post with the same title gets its own address.
        Livewire::test(CreatePost::class)
            ->fillForm(['name' => 'Playas para visitar en carro', 'content' => '<p>Otro</p>', 'status' => 'draft'])
            ->call('create');
        $this->assertSame('playas-para-visitar-en-carro-2', Post::query()->latest('id')->first()->slug->key);
    }

    public function test_contract_settings_are_shared_with_the_current_site(): void
    {
        Livewire::test(ContractSettings::class)
            ->fillForm([
                'contract_title' => 'Contrato TEST',
                'contract_min_photos' => 2,
                'contract_terms' => '<p onclick="x()">Cliente {customer_name}</p><script>alert(1)</script>',
                'contract_checklist_items' => "Gato\nExtintor",
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(Settings::class);
        $this->assertSame('Contrato TEST', Setting::get('rentcar_contract_title'));
        $this->assertSame(2, $settings->minPhotos());
        $this->assertSame(['Gato', 'Extintor'], $settings->checklistItems());
        $this->assertStringNotContainsString('onclick', $settings->terms());
        $this->assertStringNotContainsString('<script', $settings->terms());
    }
}
