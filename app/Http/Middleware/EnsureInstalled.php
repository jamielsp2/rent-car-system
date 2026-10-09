<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Until the database is set up, send every request to the install wizard.
 */
class EnsureInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        // The tables may not exist yet (or were just dropped), so a session cookie left
        // by a previous install must not trigger a user lookup (throttle, auth...).
        if (! config('app.installed')) {
            $request->setUserResolver(fn () => null);
        }

        if (! config('app.installed') && ! $request->routeIs('install.*')) {
            return redirect()->route('install.show');
        }

        return $next($request);
    }
}
