<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Route;

/**
 * Languages of the public website. Spanish is the base language (root URLs, the
 * values stored in the database columns); every other language lives under its own
 * prefix (/en/...) and its routes are named "{locale}.{name}" (en.site.home).
 */
class Lang
{
    public const DEFAULT = 'es';

    /** Languages the code can serve, with their name in their own language. */
    public const SUPPORTED = ['es' => 'Español', 'en' => 'English'];

    /** Open Graph / schema.org locale of each language. */
    public const REGIONS = ['es' => 'es_ES', 'en' => 'en_US'];

    /**
     * Languages enabled for this site (Configuración → Web pública), Spanish first.
     *
     * @return string[]
     */
    public static function enabled(): array
    {
        $saved = Setting::get('site_locales');
        $locales = $saved === null || $saved === ''
            ? array_keys(static::SUPPORTED)
            : array_values(array_intersect(array_keys(static::SUPPORTED), array_map('trim', explode(',', (string) $saved))));

        return $locales ?: [static::DEFAULT];
    }

    public static function multiple(): bool
    {
        return count(static::enabled()) > 1;
    }

    /**
     * Language of the public page being served. Only the public routes set it (SetLocale);
     * everything else, the admin included, is Spanish whatever APP_LOCALE says.
     */
    public static function current(): string
    {
        return app()->bound('lang.current') ? app('lang.current') : static::DEFAULT;
    }

    public static function isDefault(?string $locale = null): bool
    {
        return ($locale ?? static::current()) === static::DEFAULT;
    }

    public static function label(string $locale): string
    {
        return static::SUPPORTED[$locale] ?? strtoupper($locale);
    }

    public static function region(?string $locale = null): string
    {
        return static::REGIONS[$locale ?? static::current()] ?? 'es_ES';
    }

    /** Route name of the public page `$name` (site.home) in a language. */
    public static function routeName(string $name, ?string $locale = null): string
    {
        $locale ??= static::current();

        return static::isDefault($locale) ? $name : "{$locale}.{$name}";
    }

    public static function route(string $name, mixed $parameters = [], bool $absolute = true, ?string $locale = null): string
    {
        $localized = static::routeName($name, $locale);

        return route(Route::has($localized) ? $localized : $name, $parameters, $absolute);
    }

    /**
     * The page being shown, in each enabled language (same parameters and query),
     * for the language switcher and the hreflang links. Empty when it is not a public site page.
     *
     * @return array<string, string>
     */
    public static function alternates(): array
    {
        $route = request()->route();
        $name = $route?->getName();

        if (! $name) {
            return [];
        }

        $base = preg_replace('/^[a-z]{2}\./', '', $name);

        if (! str_starts_with($base, 'site.') || in_array($base, ['site.currency', 'site.checkout.store', 'site.car.quote'], true)) {
            return [];
        }

        $query = request()->getQueryString();
        $urls = [];

        foreach (static::enabled() as $locale) {
            $urls[$locale] = static::route($base, $route->parameters(), true, $locale) . ($query ? '?' . $query : '');
        }

        return $urls;
    }

    /**
     * Value of a translatable setting: the saved one in the current language, else the
     * Spanish saved one, else the default shipped with the code (translated with lang/*.json).
     */
    public static function setting(string $key, ?string $default = null): ?string
    {
        if (! static::isDefault()) {
            $translated = Setting::get($key . '_' . static::current());

            if (filled($translated)) {
                return $translated;
            }
        }

        $saved = Setting::get($key);

        if (blank($saved) || $saved === $default) {
            return $default === null ? $saved : __($default);
        }

        return $saved;
    }

    /** Long date of the language being shown: "10 de octubre, 2026" / "October 10, 2026". */
    public static function date(?\DateTimeInterface $date): string
    {
        if (! $date) {
            return '';
        }

        $date = \Illuminate\Support\Carbon::instance($date)->locale(static::current());

        return static::isDefault() ? $date->translatedFormat('d \d\e F, Y') : $date->translatedFormat('F j, Y');
    }

    /** Numeric date (and time) of the language being shown: 10/10/2026 or 10/10/2026 09:00 AM, month first in English. */
    public static function numericDate(?\DateTimeInterface $date, bool $time = false): string
    {
        if (! $date) {
            return '';
        }

        return $date->format((static::isDefault() ? 'd/m/Y' : 'm/d/Y') . ($time ? ' h:i A' : ''));
    }
}
