<?php

namespace App\Console\Commands;

use App\Support\EnvFile;
use App\Support\Installer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Same as the web installer, from the terminal: creates the tables, loads the
 * starting data and the first administrator in the database set in .env.
 */
class InstallCommand extends Command
{
    protected $signature = 'rentcar:install
        {--site= : Nombre del negocio}
        {--name= : Nombre del administrador}
        {--email= : Correo del administrador}
        {--password= : Contraseña del administrador (mínimo 8 caracteres)}
        {--demo : Cargar datos de ejemplo (no usar en un negocio real)}';

    protected $description = 'Crea las tablas, los datos iniciales y el primer administrador';

    public function handle(): int
    {
        try {
            DB::connection()->getPdo();
        } catch (Throwable $exception) {
            $this->error('No se pudo conectar a la base de datos configurada en .env: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $site = $this->option('site') ?: $this->ask('Nombre del negocio', (string) config('site.name'));
        $name = $this->option('name') ?: $this->ask('Nombre del administrador');
        $email = $this->option('email') ?: $this->ask('Correo del administrador');
        $password = $this->option('password') ?: $this->secret('Contraseña del administrador (mínimo 8 caracteres)');

        if (strlen((string) $password) < 8 || ! filter_var($email, FILTER_VALIDATE_EMAIL) || blank($name) || blank($site)) {
            $this->error('Datos incompletos: indica negocio, nombre, un correo válido y una contraseña de 8 caracteres o más.');

            return self::FAILURE;
        }

        try {
            Installer::run($site, ['name' => $name, 'email' => $email, 'password' => $password], (bool) $this->option('demo'));
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        }

        // Skip the web wizard: this installation is already set up.
        EnvFile::set(['APP_INSTALLED' => 'true']);

        $this->info('Listo. Entra en ' . rtrim((string) config('app.url'), '/') . '/admin con ' . $email);

        return self::SUCCESS;
    }
}
