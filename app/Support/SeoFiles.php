<?php

namespace App\Support;

/**
 * robots.txt as a real file (on shared hosting a missing .txt never reaches PHP), written at the
 * project root and in public/ with the address of the sitemap of this site.
 */
class SeoFiles
{
    /** Pages that are not for search engines: panel, wizard, license, carts and currency switches. */
    public const DISALLOW = [
        '/admin', '/instalar', '/licencia',
        '/moneda/', '/en/moneda/',
        '/reservar/', '/en/reservar/', '/reserva/', '/en/reserva/',
    ];

    public static function robots(?string $root = null): string
    {
        $lines = ['User-agent: *'];

        foreach (static::DISALLOW as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        if ($root) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . rtrim($root, '/') . '/sitemap';
        }

        return implode("\n", $lines) . "\n";
    }

    /** Write robots.txt next to index.php and in public/. Returns the files written. */
    public static function writeRobots(?string $root = null): array
    {
        $written = [];

        if (app()->runningUnitTests()) {
            return $written;
        }

        foreach ([base_path('robots.txt'), public_path('robots.txt')] as $path) {
            // The project root only has one when the site is served from it (shared hosting).
            if ($path === base_path('robots.txt') && ! is_file(base_path('index.php'))) {
                continue;
            }

            if (is_dir(dirname($path)) && @file_put_contents($path, static::robots($root)) !== false) {
                $written[] = $path;
            }
        }

        return $written;
    }
}
