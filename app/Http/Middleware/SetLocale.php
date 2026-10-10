<?php

namespace App\Http\Middleware;

use App\Support\Lang;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Language of a public site route, set by the route group (locale:es / locale:en).
 * A language the site has disabled answers 404.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale = Lang::DEFAULT): Response
    {
        abort_unless(array_key_exists($locale, Lang::SUPPORTED), 404);

        // Spanish pages are always served, even when only another language is enabled for the rest.
        if (! Lang::isDefault($locale) && ! in_array($locale, Lang::enabled(), true)) {
            abort(404);
        }

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        setlocale(LC_TIME, $locale === 'en' ? 'en_US.UTF-8' : 'es_ES.UTF-8');

        return $next($request);
    }
}
