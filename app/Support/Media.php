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

        $url = Storage::disk('media')->url($path);

        // Filament treats a relative URL as a file on its own disk and hides the image when
        // that file is not there. A full URL is always shown, so make the path absolute.
        if ($url && str_starts_with($url, '/')) {
            $url = rtrim(request()->getSchemeAndHttpHost(), '/') . $url;
        }

        return $url;
    }
}
