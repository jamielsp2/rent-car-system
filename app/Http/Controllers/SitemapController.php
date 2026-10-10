<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\Post;
use App\Support\Lang;
use Illuminate\Http\Response;

/**
 * sitemap.xml with every public page in each language and its hreflang alternatives.
 * A vehicle or post is listed in English only when it has an English text.
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $entries = [];

        foreach (['site.home', 'site.cars', 'site.about', 'site.faqs', 'site.blog'] as $name) {
            $entries[] = $this->entry($name, [], Lang::enabled());
        }

        Car::query()->published()->with('slug')->whereHas('slug')->orderBy('id')->get()->each(function (Car $car) use (&$entries): void {
            $entries[] = $this->entry('site.car', $car->slug->key, $this->locales($car, ['name', 'description']), $car->updated_at);
        });

        Post::query()->published()->with('slug')->whereHas('slug')->orderBy('id')->get()->each(function (Post $post) use (&$entries): void {
            $entries[] = $this->entry('site.post', $post->slug->key, $this->locales($post, ['name', 'content']), $post->updated_at ?? $post->created_at);
        });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n"
            . implode('', array_map(fn (array $entry) => $this->render($entry), $entries))
            . '</urlset>' . "\n";

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    /** @return string[] */
    protected function locales(object $model, array $keys): array
    {
        return array_values(array_filter(Lang::enabled(), fn (string $locale) => Lang::isDefault($locale) || $model->isTranslated($locale, $keys)));
    }

    /** @return array{urls: array<string, string>, lastmod: ?string} */
    protected function entry(string $name, mixed $parameters, array $locales, ?\DateTimeInterface $updated = null): array
    {
        $urls = [];

        foreach ($locales as $locale) {
            $urls[$locale] = Lang::route($name, $parameters, true, $locale);
        }

        return ['urls' => $urls, 'lastmod' => $updated?->format('Y-m-d')];
    }

    protected function render(array $entry): string
    {
        $out = '';

        foreach ($entry['urls'] as $locale => $url) {
            $out .= "  <url>\n    <loc>" . e($url) . "</loc>\n";

            if ($entry['lastmod']) {
                $out .= "    <lastmod>{$entry['lastmod']}</lastmod>\n";
            }

            if (count($entry['urls']) > 1) {
                foreach ($entry['urls'] as $alternate => $href) {
                    $out .= '    <xhtml:link rel="alternate" hreflang="' . $alternate . '" href="' . e($href) . "\"/>\n";
                }

                $out .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . e($entry['urls'][Lang::DEFAULT] ?? reset($entry['urls'])) . "\"/>\n";
            }

            $out .= "  </url>\n";
        }

        return $out;
    }
}
