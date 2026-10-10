<?php

namespace App\Http\Controllers;

use App\Support\DatabaseSetup;
use App\Support\EnvFile;
use App\Support\SeoFiles;
use App\Support\Installer;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * First-run wizard on a fresh upload: connects the database, creates the tables
 * and the first administrator. Disabled for good once it succeeds.
 */
class InstallController extends Controller
{
    public function show(Request $request): View
    {
        abort_if(config('app.installed'), 404);

        return view('install', ['url' => rtrim($request->root(), '/')]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(config('app.installed'), 404);

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'app_url' => ['required', 'url'],
            'host' => ['required', 'string', 'max:191'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:191'],
            'username' => ['required', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:191'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], [
            'site_name' => 'nombre del negocio', 'app_url' => 'dirección del sitio', 'host' => 'servidor', 'port' => 'puerto',
            'database' => 'nombre de la base de datos', 'username' => 'usuario', 'password' => 'contraseña',
            'admin_name' => 'nombre del administrador', 'admin_email' => 'correo del administrador', 'admin_password' => 'contraseña del administrador',
        ]);

        $database = collect($data)->only(['host', 'port', 'database', 'username', 'password'])->all();

        if ($error = DatabaseSetup::test($database)) {
            return $this->failed($request, $error);
        }

        try {
            DatabaseSetup::save($database, [
                'APP_NAME' => $data['site_name'],
                'APP_URL' => rtrim($data['app_url'], '/'),
            ]);

            Installer::run($data['site_name'], [
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => $data['admin_password'],
            ]);

            // Last, so a failed attempt can be retried from the wizard.
            EnvFile::set(['APP_INSTALLED' => 'true']);
            SeoFiles::writeRobots($data['app_url']);
        } catch (ValidationException $exception) {
            return back()->withInput($request->except(['password', 'admin_password', 'admin_password_confirmation']))
                ->withErrors($exception->errors());
        } catch (Throwable $exception) {
            report($exception);

            return $this->failed($request, 'La conexión funciona, pero falló la preparación de los datos: ' . $exception->getMessage());
        }

        Notification::make()->success()->title('Instalación completa')
            ->body('Entra con el correo y la contraseña que acabas de crear.')->send();

        return redirect('/admin/login');
    }

    protected function failed(Request $request, string $message): RedirectResponse
    {
        return back()
            ->withInput($request->except(['password', 'admin_password', 'admin_password_confirmation']))
            ->withErrors(['database' => $message]);
    }
}
