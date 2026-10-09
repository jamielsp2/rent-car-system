<?php

namespace App\Filament\Pages;

use App\Support\DatabaseSetup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Credentials the app uses to reach MySQL, kept in .env. Nothing is saved
 * unless the new credentials connect.
 */
class DatabaseSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Base de datos';

    protected static ?string $title = 'Base de datos';

    protected static ?int $navigationSort = 90;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(DatabaseSetup::current());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Conexión MySQL')
                    ->description('Los datos de la base de datos creada en tu hosting (en Hostinger: Bases de datos → Administración). Se comprueba la conexión antes de guardar.')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 4])->schema([
                            TextInput::make('host')->label('Servidor (host)')->required()->maxLength(191)->columnSpan(['sm' => 3]),
                            TextInput::make('port')->label('Puerto')->required()->numeric()->minValue(1)->maxValue(65535),
                        ]),
                        TextInput::make('database')->label('Nombre de la base de datos')->required()->maxLength(191),
                        TextInput::make('username')->label('Usuario')->required()->maxLength(191)->autocomplete('off'),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->helperText('Déjala vacía para conservar la contraseña actual.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('test')->label('Probar conexión')->color('gray')->icon(Heroicon::OutlinedSignal)->action('test'),
                        Action::make('save')->label('Guardar')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function test(): void
    {
        $error = DatabaseSetup::test($this->credentials());

        $error
            ? Notification::make()->danger()->title('No se pudo conectar')->body($error)->persistent()->send()
            : Notification::make()->success()->title('Conexión correcta')->send();
    }

    public function save(): void
    {
        $credentials = $this->credentials();

        if ($error = DatabaseSetup::test($credentials)) {
            Notification::make()->danger()->title('No se guardó: la conexión falló')->body($error)->persistent()->send();

            return;
        }

        if (! DatabaseSetup::hasData($credentials)) {
            Notification::make()->warning()->title('No se guardó: esa base de datos está vacía')
                ->body('Instala el sistema en ella primero (php artisan rentcar:install); si no, quedaría sin datos.')->persistent()->send();

            return;
        }

        DatabaseSetup::save($credentials);
        $this->data['password'] = null;

        Notification::make()->success()->title('Base de datos actualizada')->send();
    }

    /**
     * @return array{host: string, port: string, database: string, username: string, password: ?string}
     */
    protected function credentials(): array
    {
        $data = $this->form->getState();

        // An empty password field keeps the current one.
        $data['password'] = filled($data['password'] ?? null) ? $data['password'] : config('database.connections.mysql.password');

        return $data;
    }
}
