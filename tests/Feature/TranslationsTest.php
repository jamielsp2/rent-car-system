<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\CarType;
use App\Models\Faq;
use App\Models\Post;
use App\Support\Lang;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TranslationsTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
    }

    public function test_a_model_returns_the_text_of_the_language_being_shown(): void
    {
        $original = $this->car->name;
        $this->car->update(['translations' => ['en' => ['name' => 'English Name', 'description' => '']]]);
        $car = Car::query()->findOrFail($this->car->id);

        $this->assertSame($original, $car->name);

        app()->instance('lang.current', 'en');
        $this->assertSame('English Name', $car->name);
        // Empty translation: the Spanish text is shown.
        $this->assertSame($car->getRawOriginal('description'), $car->description);
        $this->assertTrue($car->isTranslated('en', ['name']));
        $this->assertFalse($car->isTranslated('en', ['name', 'description']) && filled($car->getRawOriginal('description')));
    }

    public function test_the_english_site_shows_translated_content_and_spanish_keeps_the_original(): void
    {
        $original = $this->car->name;
        $this->car->update(['translations' => ['en' => ['name' => 'Translated Car Name', 'description' => 'Translated summary text.']]]);

        $this->get('/en/cars/' . $this->car->slug->key)->assertOk()
            ->assertSee('Translated Car Name')
            ->assertSee('Translated summary text.');
        $this->get('/cars/' . $this->car->slug->key)->assertOk()->assertSee($original)->assertDontSee('Translated Car Name');
    }

    public function test_faqs_posts_and_lookups_are_translated(): void
    {
        $faq = Faq::query()->published()->firstOrFail();
        $faq->update(['translations' => ['en' => ['question' => 'Translated question?', 'answer' => '<p>Translated answer</p>']]]);
        $this->get('/en/faqs')->assertSee('Translated question?');
        $this->get('/faqs')->assertDontSee('Translated question?');

        $post = Post::query()->published()->with('slug')->first();

        if ($post) {
            $post->update(['translations' => ['en' => ['name' => 'Translated Post Title', 'content' => '<p>Translated body</p>']]]);
            $this->get('/en/news/' . $post->slug->key)->assertOk()->assertSee('Translated Post Title')->assertSee('Translated body');
        }

        $type = CarType::query()->findOrFail($this->car->vehicle_type_id);
        $type->update(['translations' => ['en' => ['name' => 'Translated Type']]]);
        $this->get('/en/cars/' . $this->car->slug->key)->assertSee('Translated Type');
    }

    public function test_the_admin_always_sees_the_original(): void
    {
        $original = $this->car->name;
        $this->car->update(['translations' => ['en' => ['name' => 'English Name']]]);

        // Admin and any non-public route are Spanish whatever the .env says.
        config(['app.locale' => 'en']);
        $this->actingAs(\App\Models\User::query()->where('username', 'admin')->firstOrFail())
            ->get('/admin/cars')->assertOk();
        $this->assertSame(Lang::DEFAULT, Lang::current());
        $this->assertSame($original, Car::query()->findOrFail($this->car->id)->name);
    }
}
