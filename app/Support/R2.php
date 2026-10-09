<?php

namespace App\Support;

/**
 * Almacenamiento del cliente en Cloudflare R2 (un bucket por cliente).
 *
 * Los datos del bucket llegan solos desde la plataforma de licencias (se guardan en
 * storage/app/r2.json) o se fijan a mano con R2_ENDPOINT, R2_BUCKET, R2_KEY y R2_SECRET
 * en .env. Si no hay nada, los archivos siguen en public/media como siempre.
 */
class R2
{
    /** Carpetas cuyos archivos son públicos (web y blog); todo lo demás —contratos, licencias— va firmado. */
    public const PUBLIC_FOLDERS = ['cars', 'blog', 'services', 'testimonials', 'site'];

    /** @return array{endpoint: string, bucket: string, key: string, secret: string}|null */
    public static function settings(): ?array
    {
        $env = [
            'endpoint' => env('R2_ENDPOINT'), 'bucket' => env('R2_BUCKET'),
            'key' => env('R2_KEY'), 'secret' => env('R2_SECRET'),
        ];

        if (! in_array(null, $env, true) && ! in_array('', $env, true)) {
            return $env;
        }

        $file = self::path();
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        foreach (['endpoint', 'bucket', 'key', 'secret'] as $field) {
            if (! is_array($data) || empty($data[$field])) {
                return null;
            }
        }

        return array_intersect_key($data, array_flip(['endpoint', 'bucket', 'key', 'secret']));
    }

    public static function enabled(): bool
    {
        return self::settings() !== null;
    }

    public static function path(): string
    {
        return storage_path('app/r2.json');
    }

    public static function save(array $settings): void
    {
        file_put_contents(self::path(), json_encode($settings), LOCK_EX);
        @chmod(self::path(), 0600);
    }

    public static function isPublic(string $path): bool
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        // Only files inside a public folder; never anything that tries to climb out of it.
        return str_contains($path, '/') && ! str_contains($path, '..')
            && in_array(strtok($path, '/'), self::PUBLIC_FOLDERS, true);
    }
}
