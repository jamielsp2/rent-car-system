<?php

namespace App\Support;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\Currency;
use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Everything the public pages tell search engines: canonical address, language alternates,
 * robots, Open Graph and schema.org data. Built per page and rendered by <x-site.layout>.
 */
class Seo
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 155;

    /** Plain-text description, whitespace collapsed and cut at a word. */
    public static function description(?string $text, int $limit = self::DESCRIPTION_LIMIT): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace(['</p>', '</li>', '<br>', '<br/>'], ' ', (string) $text)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return Str::limit($text, $limit, '…', true);
    }

    public static function absolute(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return preg_match('#^https?://#i', $url) ? $url : url($url);
    }

    /** Name of the current public route without its language prefix (site.car), or null. */
    public static function routeBase(): ?string
    {
        $name = request()->route()?->getName();

        return $name ? preg_replace('/^[a-z]{2}\./', '', $name) : null;
    }

    /** Clean address of the page being shown, in its language (no filters, no tracking). */
    public static function canonical(): string
    {
        $route = request()->route();
        $base = static::routeBase();

        if (! $route || ! $base || ! str_starts_with($base, 'site.')) {
            return url()->current();
        }

        $url = Lang::route($base, $route->parameters());
        $page = (int) request()->query('page');

        return $page > 1 && in_array($base, ['site.blog', 'site.cars'], true) ? $url . '?page=' . $page : $url;
    }

    /** A catalog with filters is the same list in another order or subset: keep it out of the index. */
    public static function isFiltered(): bool
    {
        return static::routeBase() === 'site.cars' && collect(request()->query())->except('page')->isNotEmpty();
    }

    /**
     * Address of this page in each language it can be indexed in, for hreflang (no query string).
     *
     * @param  string[]|null  $only  limit to these languages (a page not translated yet is left out)
     * @return array<string, string>
     */
    public static function alternates(?array $only = null): array
    {
        $route = request()->route();
        $base = static::routeBase();

        if (! $route || ! $base || ! str_starts_with($base, 'site.')) {
            return [];
        }

        $urls = [];

        foreach (Lang::enabled() as $locale) {
            if ($only === null || in_array($locale, $only, true)) {
                $urls[$locale] = Lang::route($base, $route->parameters(), true, $locale);
            }
        }

        return count($urls) > 1 ? $urls : [];
    }

    public static function json(array $schema): string
    {
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    public static function currencyCode(): string
    {
        return Currency::default()?->title ?: 'USD';
    }

    /** The business, for the home and about pages. */
    public static function organization(): array
    {
        $contact = SiteContent::contact();
        $social = SiteContent::social();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'AutoRental',
            '@id' => url('/') . '#business',
            'name' => SiteContent::name(),
            'url' => Lang::route('site.home'),
            'logo' => static::absolute(SiteContent::logo()),
            'image' => static::absolute(SiteContent::hero()['image'] ?? SiteContent::logo()),
            'telephone' => $contact['phone'] ?: null,
            'email' => $contact['email'] ?: null,
            'address' => $contact['address'] ? ['@type' => 'PostalAddress', 'streetAddress' => $contact['address']] : null,
            'sameAs' => array_values(array_filter([$social['facebook'], $social['instagram']])) ?: null,
            'inLanguage' => Lang::current(),
        ]);
    }

    /** @param  array<int, array{0: string, 1: string}>  $items  [name, url] pairs, from the home page */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)->values()->map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item[0],
                'item' => $item[1],
            ])->all(),
        ];
    }

    /** Languages a model can be indexed in: Spanish, plus the ones with its translated text. */
    protected static function locales(object $model, array $keys): array
    {
        return array_values(array_filter(Lang::enabled(), fn (string $locale) => Lang::isDefault($locale) || $model->isTranslated($locale, $keys)));
    }

    /**
     * @param  Collection<int, string>  $photos  absolute or relative photo URLs
     * @return array{title_full: ?string, title: string, description: string, image: ?string, type: string, locales: string[], noindex: bool, schema: array}
     */
    public static function car(Car $car, Collection $photos): array
    {
        $locales = static::locales($car, ['name', 'description']);
        $canonical = static::canonical();
        $images = $photos->map(fn ($url) => static::absolute($url))->filter()->values();
        $description = $car->seoText('description') ?: static::description($car->description ?: $car->content);
        $available = $car->status === CarStatus::Available;
        $rate = (float) $car->rental_rate;

        return [
            'title_full' => $car->seoText('title'),
            'title' => $car->name . ' - ' . __('Alquiler de vehículos'),
            'description' => $description,
            'image' => $images->first(),
            'type' => 'product',
            'locales' => $locales,
            'noindex' => ! in_array(Lang::current(), $locales, true),
            'schema' => [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => ['Product', 'Car'],
                    'name' => $car->name,
                    'description' => $description,
                    'image' => $images->all() ?: null,
                    'url' => $canonical,
                    'inLanguage' => Lang::current(),
                    'brand' => $car->make ? ['@type' => 'Brand', 'name' => $car->make->name] : null,
                    'model' => $car->model_name ?: null,
                    'vehicleModelDate' => $car->year ?: null,
                    'bodyType' => $car->type?->name,
                    'vehicleTransmission' => $car->transmission?->name,
                    'fuelType' => $car->fuel?->name,
                    'numberOfDoors' => $car->number_of_doors ?: null,
                    'vehicleSeatingCapacity' => $car->number_of_seats ?: null,
                    'offers' => $rate > 0 ? [
                        '@type' => 'Offer',
                        'url' => $canonical,
                        'price' => number_format($rate, 2, '.', ''),
                        'priceCurrency' => static::currencyCode(),
                        'availability' => 'https://schema.org/' . ($available ? 'InStock' : 'OutOfStock'),
                        'priceSpecification' => [
                            '@type' => 'UnitPriceSpecification',
                            'price' => number_format($rate, 2, '.', ''),
                            'priceCurrency' => static::currencyCode(),
                            'unitText' => 'DAY',
                        ],
                    ] : null,
                ]),
                static::breadcrumbs([
                    [__('Inicio'), Lang::route('site.home')],
                    [__('Nuestros vehículos'), Lang::route('site.cars')],
                    [$car->name, $canonical],
                ]),
            ],
        ];
    }

    /** @return array{title_full: ?string, title: string, description: string, image: ?string, type: string, locales: string[], noindex: bool, schema: array} */
    public static function post(Post $post): array
    {
        $locales = static::locales($post, ['name', 'content']);
        $canonical = static::canonical();
        $description = $post->seoText('description') ?: static::description($post->description ?: $post->content);
        $image = static::absolute($post->image_url);

        return [
            'title_full' => $post->seoText('title'),
            'title' => $post->name . ' - Blog',
            'description' => $description,
            'image' => $image,
            'type' => 'article',
            'locales' => $locales,
            'noindex' => ! in_array(Lang::current(), $locales, true),
            'schema' => [
                array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'BlogPosting',
                    'headline' => Str::limit($post->name, 110, ''),
                    'description' => $description,
                    'image' => $image,
                    'inLanguage' => Lang::current(),
                    'datePublished' => $post->created_at?->toAtomString(),
                    'dateModified' => ($post->updated_at ?? $post->created_at)?->toAtomString(),
                    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
                    'author' => ['@type' => 'Organization', 'name' => SiteContent::name()],
                    'publisher' => array_filter(['@type' => 'Organization', 'name' => SiteContent::name(), 'logo' => ($logo = static::absolute(SiteContent::logo())) ? ['@type' => 'ImageObject', 'url' => $logo] : null]),
                ]),
                static::breadcrumbs([
                    [__('Inicio'), Lang::route('site.home')],
                    ['Blog', Lang::route('site.blog')],
                    [$post->name, $canonical],
                ]),
            ],
        ];
    }

    /** @param  iterable<\App\Models\Faq>  $faqs */
    public static function faqs(iterable $faqs): array
    {
        $entities = collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => trim(strip_tags((string) $faq->question)),
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags((string) $faq->safe_answer))],
        ])->filter(fn (array $q) => $q['name'] !== '' && $q['acceptedAnswer']['text'] !== '')->values()->all();

        return array_filter([
            $entities ? ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'inLanguage' => Lang::current(), 'mainEntity' => $entities] : null,
            static::breadcrumbs([[__('Inicio'), Lang::route('site.home')], [__('Preguntas frecuentes'), Lang::route('site.faqs')]]),
        ]);
    }

    /** Pages with only a trail: catalog, blog, about... */
    public static function trail(string $name, string $routeName): array
    {
        return [static::breadcrumbs([[__('Inicio'), Lang::route('site.home')], [$name, Lang::route($routeName)]])];
    }
}
