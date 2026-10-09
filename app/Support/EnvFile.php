<?php

namespace App\Support;

use RuntimeException;

/**
 * Updates keys in the application's .env file, keeping everything else as is.
 */
class EnvFile
{
    /**
     * @param  array<string, string>  $values
     */
    public static function set(array $values, ?string $path = null): void
    {
        $path ??= app()->environmentFilePath();
        $content = is_file($path) ? file_get_contents($path) : '';

        foreach ($values as $key => $value) {
            $line = $key . '=' . static::quote($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            $content = preg_match($pattern, $content)
                ? preg_replace_callback($pattern, fn () => $line, $content)
                : rtrim($content) . "\n" . $line . "\n";
        }

        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo escribir el archivo .env. Revisa los permisos de la carpeta.');
        }
    }

    /**
     * Quote values that dotenv would otherwise misread (spaces, #, quotes, $).
     */
    protected static function quote(string $value): string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_.:\/@-]+$/', $value)) {
            return $value;
        }

        return '"' . str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value) . '"';
    }
}
