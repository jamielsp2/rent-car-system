<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Paths of uploaded files, relative to the media disk. They may have been
 * saved with Windows separators ("contracts\x.png"); normalize before using them.
 */
class Media
{
    public static function path(?string $path): ?string
    {
        return $path ? ltrim(str_replace('\\', '/', $path), '/') : null;
    }

    public static function url(?string $path): ?string
    {
        $path = static::path($path);

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('media')->url($path);
    }
}
