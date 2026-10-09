<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\URL;

/**
 * Disco «media» sobre un bucket R2 privado. Las URL no apuntan al bucket: pasan por
 * /media-r2 (la app lee el archivo y lo sirve con caché larga). Las carpetas públicas
 * llevan una URL normal; las privadas (contratos, licencias) llevan una URL firmada
 * que caduca.
 *
 * El destino es una URL sin extensión de archivo ni carpetas, porque hay hostings que
 * solo pasan a PHP las rutas que no parecen un archivo estático.
 */
class R2FilesystemAdapter extends FilesystemAdapter
{
    public function url($path)
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        if (R2::isPublic($path)) {
            return route('media.r2', ['f' => $path]);
        }

        return $this->temporaryUrl($path, now()->addHours(12));
    }

    public function temporaryUrl($path, $expiration, array $options = [])
    {
        $expiration = $expiration instanceof DateTimeInterface ? $expiration : now()->addHours(12);

        return URL::temporarySignedRoute('media.r2', $expiration, ['f' => ltrim(str_replace('\\', '/', (string) $path), '/')]);
    }
}
