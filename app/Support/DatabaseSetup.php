<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;

/**
 * Database credentials kept in .env: test them, save them and look inside.
 */
class DatabaseSetup
{
    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: ?string}  $credentials
     * @return string|null Error message, or null when the connection works.
     */
    public static function test(array $credentials): ?string
    {
        try {
            static::pdo($credentials)->query('SELECT 1');

            return null;
        } catch (PDOException $exception) {
            return static::explain($exception);
        }
    }

    /**
     * Whether the database already holds this system's tables.
     */
    public static function hasData(array $credentials): bool
    {
        $statement = static::pdo($credentials)->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $statement->execute([$credentials['database'], 'cr_cars']);

        return (int) $statement->fetchColumn() > 0;
    }

    /**
     * Write the credentials to .env and point the running app at them.
     *
     * @param  array<string, string>  $extra  More .env values to set.
     */
    public static function save(array $credentials, array $extra = []): void
    {
        EnvFile::set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $credentials['host'],
            'DB_PORT' => (string) $credentials['port'],
            'DB_DATABASE' => $credentials['database'],
            'DB_USERNAME' => $credentials['username'],
            'DB_PASSWORD' => (string) $credentials['password'],
            ...$extra,
        ]);

        // A cached config would ignore the new .env.
        Artisan::call('config:clear');

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $credentials['host'],
            'database.connections.mysql.port' => $credentials['port'],
            'database.connections.mysql.database' => $credentials['database'],
            'database.connections.mysql.username' => $credentials['username'],
            'database.connections.mysql.password' => $credentials['password'],
        ]);
        DB::purge('mysql');
    }

    /**
     * @return array{host: string, port: string, database: string, username: string}
     */
    public static function current(): array
    {
        $config = config('database.connections.mysql');

        return [
            'host' => (string) $config['host'],
            'port' => (string) $config['port'],
            'database' => (string) $config['database'],
            'username' => (string) $config['username'],
        ];
    }

    protected static function pdo(array $credentials): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $credentials['host'], $credentials['port'], $credentials['database']);

        return new PDO($dsn, $credentials['username'], (string) $credentials['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 8,
        ]);
    }

    /**
     * Plain-language reason for the usual connection failures.
     */
    protected static function explain(PDOException $exception): string
    {
        $code = (int) ($exception->errorInfo[1] ?? $exception->getCode());

        return match ($code) {
            1045 => 'Usuario o contraseña incorrectos.',
            1049 => 'La base de datos no existe. Créala primero en el panel de tu hosting.',
            1044 => 'El usuario no tiene permiso sobre esa base de datos.',
            2002, 2005 => 'No se pudo llegar al servidor de base de datos. Revisa el host y el puerto.',
            default => 'No se pudo conectar: ' . $exception->getMessage(),
        };
    }
}
