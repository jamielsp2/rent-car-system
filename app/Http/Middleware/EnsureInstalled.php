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
        if (! config('app.installed') && ! $request->routeIs('install.*')) {
            return redirect()->route('install.show');
        }

        return $next($request);
    }
}
