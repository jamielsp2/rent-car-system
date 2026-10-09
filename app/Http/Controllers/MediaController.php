<?php

namespace App\Http\Controllers;

use App\Support\R2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sirve los archivos del bucket R2 del cliente. Públicos: fotos de la web. Privados
 * (contratos, licencias de conducir): solo con la URL firmada que genera el panel.
 */
class MediaController extends Controller
{
    public function show(Request $request): Response
    {
        $path = ltrim(str_replace('\\', '/', (string) $request->query('f')), '/');

        // Never leave the bucket's own paths, and never serve hidden files.
        abort_if($path === '' || str_contains($path, '..') || str_starts_with(basename($path), '.') || ! R2::enabled(), 404);

        if (! R2::isPublic($path)) {
            abort_unless($request->hasValidSignature(), 403);
        }

        $disk = Storage::disk('media');

        abort_unless($disk->exists($path), 404);

        $cache = R2::isPublic($path) ? 'public, max-age=31536000, immutable' : 'private, max-age=3600';

        return $disk->response($path, null, [
            'Cache-Control' => $cache,
            'X-Content-Type-Options' => 'nosniff',
            // An uploaded SVG opened directly must not be able to run scripts on this origin.
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
        ]);
    }
}
