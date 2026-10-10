<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Faq;
use App\Models\Post;
use App\Support\Lang;
use App\Support\Seo;
use App\Support\SeoFiles;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use DatabaseTransactions;

    protected Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->car = Car::query()->published()->whereHas('slug')->with('slug')->firstOrFail();
        $this->car->update(['translations' => ['en' => ['name' => 'English Car', 'description' => 'English summary for the car.']]]);
    }

    /** @return array<int, array<string, mixed>> the JSON-LD blocks of a page */
    protected function schemas(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
    }

    public function test_every_page_declares_its_language_canonical_and_alternates(): void
    {
        $key = $this->car->slug->key;
        $es = $this->get("/cars/{$key}")->assertOk()
            ->assertSee('<link rel="canonical" href="' . route('site.car', $key) . '">', false)
            ->assertSee('<link rel="alternate" hreflang="es" href="' . route('site.car', $key) . '">', false)
            ->assertSee('<link rel="alternate" hreflang="en" href="' . route('en.site.car', $key) . '">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="' . route('site.car', $key) . '">', false)
            ->assertSee('<meta property="og:locale" content="es_ES">', false)
            ->assertSee('<meta property="og:locale:alternate" content="en_US">', false);
        $this->assertStringContainsString('index, follow', $es->getContent());

        $this->get("/en/cars/{$key}")->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('<link rel="canonical" href="' . route('en.site.car', $key) . '">', false)
            ->assertSee('<meta property="og:locale" content="en_US">', false)
            ->assertSee('<title>English Car', false);
    }

    public function test_the_canonical_drops_filters_and_filtered_catalogs_are_not_indexed(): void
    {
        $key = $this->car->slug->key;
        $this->get("/cars/{$key}?from=2099-01-01&to=2099-01-04")
            ->assertSee('<link rel="canonical" href="' . route('site.car', $key) . '">', false)
            ->assertSee('index, follow', false);

        $this->get('/cars')->assertSee('index, follow', false)->assertSee('<link rel="canonical" href="' . route('site.cars') . '">', false);
        $this->get('/cars?sort=price_asc')->assertSee('noindex, follow', false)
            ->assertSee('<link rel="canonical" href="' . route('site.cars') . '">', false)
            ->assertDontSee('rel="alternate"', false);
    }

    public function test_a_page_without_english_text_is_kept_out_of_the_index_in_english(): void
    {
        $key = $this->car->slug->key;
        $this->car->update(['translations' => null]);

        $en = $this->get("/en/cars/{$key}")->assertOk()->assertSee('noindex, follow', false);
        $this->assertStringNotContainsString('rel="alternate" hreflang="en"', $en->getContent());
        // Spanish stays indexable and does not point to an English page that does not exist.
        $this->get("/cars/{$key}")->assertSee('index, follow', false)->assertDontSee('rel="alternate" hreflang="en"', false);
    }

    public function test_checkout_and_confirmation_are_noindex(): void
    {
        $from = now()->addDays(40)->toDateString();
        $to = now()->addDays(43)->toDateString();

        $this->get("/reservar/{$this->car->slug->key}?from={$from}&to={$to}")->assertOk()->assertSee('noindex, follow', false);
    }

    public function test_the_car_page_has_product_and_breadcrumb_schema_in_its_language(): void
    {
        $key = $this->car->slug->key;
        $schemas = $this->schemas($this->get("/en/cars/{$key}")->getContent());

        $product = collect($schemas)->first(fn ($s) => in_array('Product', (array) $s['@type'], true));
        $this->assertSame('English Car', $product['name']);
        $this->assertSame('English summary for the car.', $product['description']);
        $this->assertSame('en', $product['inLanguage']);
        $this->assertSame(route('en.site.car', $key), $product['url']);
        $this->assertSame('DAY', $product['offers']['priceSpecification']['unitText']);
        $this->assertEqualsWithDelta((float) $this->car->rental_rate, (float) $product['offers']['price'], 0.01);

        $crumbs = collect($schemas)->firstWhere('@type', 'BreadcrumbList');
        $this->assertSame(['Home', 'Our vehicles', 'English Car'], array_column($crumbs['itemListElement'], 'name'));
    }

    public function test_custom_google_title_and_description_win_over_the_generated_ones(): void
    {
        $this->car->update(['seo' => [
            'es' => ['title' => 'Título SEO español', 'description' => 'Descripción SEO español.'],
            'en' => ['title' => 'English SEO title', 'description' => 'English SEO description.'],
        ]]);
        $key = $this->car->slug->key;

        $this->get("/cars/{$key}")->assertSee('<title>Título SEO español</title>', false)
            ->assertSee('<meta name="description" content="Descripción SEO español.">', false);
        $this->get("/en/cars/{$key}")->assertSee('<title>English SEO title</title>', false)
            ->assertSee('<meta property="og:description" content="English SEO description.">', false)
            ->assertOk();
    }

    public function test_faq_blog_and_home_schema(): void
    {
        $faqSchema = collect($this->schemas($this->get('/en/faqs')->getContent()))->firstWhere('@type', 'FAQPage');
        $this->assertNotEmpty($faqSchema['mainEntity']);
        $this->assertSame('Question', $faqSchema['mainEntity'][0]['@type']);

        $home = $this->schemas($this->get('/')->getContent());
        $this->assertSame('AutoRental', $home[0]['@type']);

        $post = Post::query()->published()->with('slug')->first();

        if ($post) {
            $blog = collect($this->schemas($this->get('/news/' . $post->slug->key)->getContent()))->firstWhere('@type', 'BlogPosting');
            $this->assertSame($post->name, $blog['headline']);
        }
    }

    public function test_sitemap_lists_both_languages_with_alternates_and_skips_untranslated_ones(): void
    {
        $key = $this->car->slug->key;
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=utf-8')->getContent();

        $this->assertStringContainsString('<loc>' . route('site.home') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . route('en.site.home') . '</loc>', $xml);
        $this->assertStringContainsString('<loc>' . route('en.site.car', $key) . '</loc>', $xml);
        $this->assertStringContainsString('hreflang="x-default" href="' . route('site.car', $key) . '"', $xml);
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->get('/sitemap')->assertOk();

        // Another vehicle without English text: only its Spanish address.
        $other = Car::query()->published()->whereHas('slug')->with('slug')->whereKeyNot($this->car->id)->firstOrFail();
        $other->update(['translations' => null]);
        $xml = $this->get('/sitemap')->getContent();
        $this->assertStringContainsString('<loc>' . route('site.car', $other->slug->key) . '</loc>', $xml);
        $this->assertStringNotContainsString(route('en.site.car', $other->slug->key), $xml);
    }

    public function test_robots_blocks_private_areas_and_points_to_the_sitemap(): void
    {
        $robots = SeoFiles::robots('https://example.com/');

        $this->assertStringContainsString("Disallow: /admin\n", $robots);
        $this->assertStringContainsString("Disallow: /en/reservar/\n", $robots);
        $this->assertStringContainsString("Sitemap: https://example.com/sitemap\n", $robots);
        $this->assertStringNotContainsString('Sitemap', SeoFiles::robots());
    }

    public function test_descriptions_are_cut_at_a_word_without_html(): void
    {
        $text = Seo::description('<p>' . str_repeat('palabra ', 60) . '</p>');

        $this->assertLessThanOrEqual(Seo::DESCRIPTION_LIMIT + 1, mb_strlen($text));
        $this->assertStringEndsWith('…', $text);
        $this->assertStringNotContainsString('<', $text);
        $this->assertSame(['es', 'en'], Lang::enabled());
    }
}
