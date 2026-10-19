<?php

namespace App\Filament\Pages;

use App\Support\License;
use App\Support\R2;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Licencia del sistema: estado actual, consulta a la plataforma cuando se quiera y
 * cambio del código de licencia.
 */
class LicensePage extends Page
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'configuracion';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Licencia';

    protected static ?string $title = 'Licencia';

    protected static ?string $slug = 'licencia';

    protected static ?int $navigationSort = 95;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function getNavigationBadge(): ?string
    {
        return (License::notice()['level'] ?? null) === 'danger' ? '!' : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Textarea::make('code')
                    ->label('Código de licencia')
                    ->placeholder('RCS1.eyJ2Ijox…')
                    ->rows(4)
                    ->extraInputAttributes(['class' => 'font-mono text-xs', 'autocomplete' => 'off', 'spellcheck' => 'false'])
                    ->helperText('Pega aquí el código completo que te entregó tu proveedor. Reemplaza al actual.'),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Estado')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->headerActions([
                    Action::make('check')->label('Comprobar ahora')->color('gray')->icon(Heroicon::OutlinedArrowPath)->action('check'),
                ])
                ->schema([
                    View::make('filament.pages.license-status')->viewData([
                        'license' => License::current(),
                        'notice' => License::notice(),
                        'checkedAt' => License::lastCheckedAt(),
                        'storage' => R2::settings()['bucket'] ?? null,
                    ]),
                ]),
            Section::make('Cambiar la licencia')
                ->description('Para activar una licencia nueva, renovada o de otro plan.')
                ->icon(Heroicon::OutlinedKey)
                ->schema([
                    Form::make([EmbeddedSchema::make('form')])
                        ->id('form')
                        ->livewireSubmitHandler('activate')
                        ->footer([
                            Actions::make([
                                Action::make('activate')->label('Activar licencia')->submit('activate'),
                            ]),
                        ]),
                ]),
        ]);
    }

    public function activate(): void
    {
        $code = trim((string) ($this->form->getState()['code'] ?? ''));

        if ($code === '') {
            Notification::make()->warning()->title('Pega el código de licencia')->send();

            return;
        }

        try {
            $payload = License::activate($code);
        } catch (ValidationException $exception) {
            Notification::make()->danger()->title('No se activó')->body(collect($exception->errors())->flatten()->implode(' '))->persistent()->send();

            return;
        }

        $this->form->fill();

        Notification::make()->success()->title('Licencia activada')
            ->body('Cliente: ' . $payload['client'] . '. Válida hasta el ' . date('d/m/Y', $payload['exp']) . '.')->send();
    }

    public function check(): void
    {
        License::checkOnline()
            ? Notification::make()->success()->title('Licencia comprobada')->body('Se consultó a la plataforma de licencias.')->send()
            : Notification::make()->warning()->title('No se pudo consultar la plataforma')->body('Sin conexión o sin dirección de consulta. La licencia sigue valiendo hasta su fecha.')->send();
    }
}
