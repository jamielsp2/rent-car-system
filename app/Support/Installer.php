<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\DefaultDataSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Prepares an empty database: creates the tables, loads the starting data and
 * the first administrator. Used by the web installer (/instalar) and by the
 * `php artisan rentcar:install` command.
 */
class Installer
{
    /**
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public static function run(string $siteName, array $admin, bool $demo = false): User
    {
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => DefaultDataSeeder::class, '--force' => true]);

        Setting::put(['site_name' => $siteName]);
        Cache::forget('settings.all');

        $user = static::createAdmin($admin);

        if ($demo) {
            Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);
        }

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public static function createAdmin(array $data): User
    {
        if (User::query()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages(['admin_email' => 'Ya existe un usuario con ese correo.']);
        }

        [$first, $last] = array_pad(explode(' ', trim($data['name']), 2), 2, '');

        $user = new User([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $data['email'],
            'username' => static::username($data['email']),
            'password' => $data['password'],
            'super_user' => true,
        ]);
        $user->save();

        return $user;
    }

    protected static function username(string $email): string
    {
        $base = Str::slug(Str::before($email, '@')) ?: 'admin';
        $username = $base;

        for ($i = 2; User::query()->where('username', $username)->exists(); $i++) {
            $username = "{$base}{$i}";
        }

        return $username;
    }
}
