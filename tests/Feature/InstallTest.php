<?php

namespace Tests\Feature;

use App\Filament\Pages\DatabaseSettings;
use App\Models\User;
use App\Support\DatabaseSetup;
use App\Support\Installer;
use App\Support\SqlScript;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class InstallTest extends TestCase
{
    protected function credentials(array $overrides = []): array
    {
        $config = config('database.connections.mysql');

        return array_merge([
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
        ], $overrides);
    }

    public function test_a_fresh_upload_sends_everyone_to_the_wizard(): void
    {
        config(['app.installed' => false]);

        $this->get('/')->assertRedirect(route('install.show'));
        $this->get('/admin/login')->assertRedirect(route('install.show'));
        $this->get('/instalar')->assertOk()->assertSee('Pon en marcha tu sistema');
    }

    public function test_the_wizard_is_gone_once_installed(): void
    {
        $this->get('/instalar')->assertNotFound();
        $this->post('/instalar')->assertNotFound();
    }

    public function test_the_wizard_checks_what_it_is_given(): void
    {
        config(['app.installed' => false]);

        $this->from('/instalar')->post('/instalar', ['site_name' => 'Mi negocio'])
            ->assertRedirect('/instalar')
            ->assertSessionHasErrors(['app_url', 'host', 'database', 'admin_email', 'admin_password']);
    }

    public function test_wrong_credentials_are_explained_and_nothing_is_saved(): void
    {
        config(['app.installed' => false]);
        $env = file_get_contents(base_path('.env'));

        $this->from('/instalar')->post('/instalar', [
            'site_name' => 'Mi negocio',
            'app_url' => 'https://example.test',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'no_existe_' . uniqid(),
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'admin_name' => 'Ana Admin',
            'admin_email' => 'ana@example.test',
            'admin_password' => 'clave-segura-1',
            'admin_password_confirmation' => 'clave-segura-1',
        ])->assertRedirect('/instalar')->assertSessionHasErrors('database');

        $this->assertSame($env, file_get_contents(base_path('.env')));
        $this->assertNotNull(DatabaseSetup::test($this->credentials(['password' => 'mal-' . uniqid(), 'username' => 'nadie'])));
        $this->assertNull(DatabaseSetup::test($this->credentials()));
    }

    public function test_sql_scripts_run_statement_by_statement(): void
    {
        $script = tempnam(sys_get_temp_dir(), 'sql');
        file_put_contents($script, "-- comentario\nDROP TABLE IF EXISTS `install_probe`;\nCREATE TABLE `install_probe` (\n  `id` int NOT NULL,\n  `name` varchar(20)\n);\nINSERT INTO `install_probe` VALUES (1,'uno;dos'),(2,'tres');\n");

        try {
            $this->assertSame(3, SqlScript::run(DB::connection()->getPdo(), $script));
            $this->assertSame(2, DB::table('install_probe')->count());
            $this->assertSame('uno;dos', DB::table('install_probe')->where('id', 1)->value('name'));
        } finally {
            DB::statement('DROP TABLE IF EXISTS `install_probe`');
            unlink($script);
        }
    }

    public function test_the_schema_file_lists_every_table_the_models_use(): void
    {
        $tables = SqlScript::tables(database_path('schema/rent_car.sql'));

        foreach (['cr_cars', 'cr_bookings', 'cr_booking_contracts', 'cr_customers', 'users', 'settings', 'slugs', 'faqs'] as $table) {
            $this->assertContains($table, $tables);
        }
    }

    public function test_the_first_administrator_is_created_once(): void
    {
        $user = Installer::createAdmin(['name' => 'Laura Gómez Pérez', 'email' => 'laura@example.test', 'password' => 'una-clave-larga']);

        $this->assertSame('Laura', $user->first_name);
        $this->assertSame('Gómez Pérez', $user->last_name);
        $this->assertTrue($user->super_user);
        $this->assertNotSame('una-clave-larga', $user->password);

        $this->expectException(ValidationException::class);
        Installer::createAdmin(['name' => 'Otra Laura', 'email' => 'laura@example.test', 'password' => 'otra-clave-larga']);
    }

    public function test_admins_can_check_and_change_the_database(): void
    {
        $this->actingAs(User::query()->firstOrFail());
        $this->get(DatabaseSettings::getUrl())->assertOk()->assertSee('Conexión MySQL');

        // A database that doesn't connect is never saved.
        $env = file_get_contents(base_path('.env'));
        Livewire::test(DatabaseSettings::class)
            ->fillForm(['database' => 'no_existe_' . uniqid()])
            ->call('save')
            ->assertNotified('No se guardó: la conexión falló');
        $this->assertSame($env, file_get_contents(base_path('.env')));

        Livewire::test(DatabaseSettings::class)->call('test')->assertNotified('Conexión correcta');
    }
}
