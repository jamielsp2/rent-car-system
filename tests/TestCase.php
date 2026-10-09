<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private static bool $databaseReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$databaseReady) {
            self::prepareDatabase();
            self::$databaseReady = true;
        }
    }

    /**
     * Once per test run: a dedicated database with the schema, the starting data
     * and the made-up demo data (cars, bookings...). Each test then works inside a
     * transaction that is rolled back, so the data is the same for all of them.
     */
    private static function prepareDatabase(): void
    {
        $config = config('database.connections.mysql');

        // The database is wiped and rebuilt: never let that happen to a real one.
        if (! str_ends_with((string) $config['database'], '_test')) {
            throw new RuntimeException("La base de datos de pruebas debe terminar en _test (es \"{$config['database']}\"). Revisa DB_DATABASE en phpunit.xml.");
        }

        $pdo = new PDO(
            sprintf('mysql:host=%s;port=%s', $config['host'], $config['port']),
            $config['username'],
            (string) $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        DB::purge('mysql');

        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        (new User(['first_name' => 'Admin', 'last_name' => 'Pruebas', 'email' => 'admin@example.test', 'username' => 'admin', 'password' => 'secret-password', 'super_user' => true]))->save();
    }
}
