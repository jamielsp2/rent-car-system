<?php

namespace App\Http\Middleware;

use App\Support\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sin una licencia vigente el sistema no funciona: todo lleva a /licencia, donde se
 * pega el código que entrega la plataforma de licencias.
 */
class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        // The installer comes first; the license page itself must stay reachable.
        if (app()->runningUnitTests() || ! config('app.installed') || $request->routeIs('install.*', 'license.*')) {
            return $next($request);
        }

        License::checkOnlineIfDue();

        if (License::allows()) {
            return $next($request);
        }

        // Do not depend on the database (or a stale session) just to show the license page.
        $request->setUserResolver(fn () => null);

        return redirect()->route('license.show');
    }
}
