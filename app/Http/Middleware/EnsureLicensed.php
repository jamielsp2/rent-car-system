<?php

namespace App\Http\Middleware;

use App\Support\License;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * La licencia no bloquea el sistema: cuando falta, está suspendida o venció, el panel de
 * administración muestra un aviso fijo («este tenant no está activado») que lleva a /licencia.
 * Aquí solo se mantiene al día el estado: cada cierto tiempo se consulta a la plataforma de
 * licencias, siempre DESPUÉS de enviar la respuesta para que nadie espere por ello.
 */
class EnsureLicensed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->runningUnitTests() && config('app.installed') && ! $request->routeIs('install.*', 'media.r2') && License::isCheckDue()) {
            License::markChecked();
            app()->terminating(fn () => License::checkOnline());
        }

        return $next($request);
    }
}
