<?php

namespace App\Http\Controllers;

use App\Support\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Página /licencia: estado de la licencia y formulario para pegar el código.
 */
class LicenseController extends Controller
{
    public function show(): View
    {
        return view('license', ['license' => License::current()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:4000']], [], ['code' => 'código de licencia']);

        try {
            $payload = License::activate($data['code']);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->route('license.show')->with('status', 'Licencia activada para ' . $payload['client'] . ', válida hasta el ' . date('d/m/Y', $payload['exp']) . '.');
    }

    /** «Comprobar ahora»: consulta a la plataforma si ya hay una renovación. */
    public function check(): RedirectResponse
    {
        if (! License::checkOnline()) {
            return back()->with('warning', 'No se pudo consultar la plataforma de licencias. Inténtalo más tarde o pega el código renovado.');
        }

        return back()->with('status', 'Licencia comprobada.');
    }
}
