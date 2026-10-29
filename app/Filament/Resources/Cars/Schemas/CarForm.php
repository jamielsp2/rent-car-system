<?php

namespace App\Filament\Resources\Cars\Schemas;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\User;
use App\Filament\Support\SeoSection;
use App\Filament\Support\TranslationSection;
use App\Services\CarCopywriter;
use App\Support\Lang;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CarForm
{
    /** "Generar con IA" button: writes `$field` ('summary' or 'content') from the data filled in above. */
    protected static function aiAction(string $field): Action
    {
        $target = $field === 'summary' ? 'description' : 'content';

        return Action::make("generate_{$field}_ai")
            ->label('Generar con IA')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('primary')
            ->visible(fn () => CarCopywriter::available())
            ->requiresConfirmation(fn (Get $get) => filled($get($target)))
            ->modalHeading('Reemplazar el texto actual')
            ->modalDescription('Ya hay texto escrito. Si continúas se reemplaza por uno nuevo generado con IA.')
            ->action(function (Get $get, Set $set) use ($field, $target): void {
                try {
                    $text = app(CarCopywriter::class)->generate([
                        'name' => $get('name'), 'make_id' => $get('make_id'), 'model_name' => $get('model_name'),
                        'year' => $get('year'), 'vehicle_type_id' => $get('vehicle_type_id'),
                        'transmission_id' => $get('transmission_id'), 'fuel_type_id' => $get('fuel_type_id'),
                        'number_of_seats' => $get('number_of_seats'), 'number_of_doors' => $get('number_of_doors'),
                        'summary' => $get('description'), 'mileage' => $get('mileage'), 'rental_rate' => $get('rental_rate'), 'min_rental_days' => $get('min_rental_days'),
                    ]);
                } catch (\Throwable $exception) {
                    Notification::make()->danger()->title('No se pudo generar el texto')->body($exception->getMessage())->send();

                    return;
                }

                $set($target, $text[$field]);

                // The English version is written at the same time, so both stay in sync.
                if (Lang::multiple()) {
                    $set("translations.en.{$target}", $text['en'][$field]);
                }

                Notification::make()->success()->title('Texto generado')
                    ->body(Lang::multiple() ? 'Se generó en español y en inglés. Revísalo y ajústalo antes de guardar.' : 'Revísalo y ajústalo antes de guardar.')->send();
            });
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Vehículo')
                        ->columns(['default' => 1, 'sm' => 2, 'xl' => 3])
                        ->schema([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->placeholder('Toyota RAV4 2023')
                                ->required()
                                ->maxLength(191)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, ?string $old, Set $set, callable $get): void {
                                    // Suggest the address from the name until it's edited by hand.
                                    if (blank($get('slug_key')) || $get('slug_key') === Str::slug((string) $old)) {
                                        $set('slug_key', Str::slug((string) $state));
                                    }
                                })
                                ->columnSpanFull(),
                            TextInput::make('slug_key')
                                ->label('Dirección en la web')
                                ->prefix(url('/cars') . '/')
                                ->helperText('Se completa sola a partir del nombre.')
                                ->maxLength(191)
                                ->dehydrated(false)
                                ->afterStateHydrated(fn (TextInput $component, ?Car $record) => $component->state($record?->slug?->key))
                                ->columnSpanFull(),
                            Select::make('make_id')->label('Marca')->relationship('make', 'name')->searchable()->preload(),
                            TextInput::make('model_name')->label('Modelo')->maxLength(120),
                            TextInput::make('year')->label('Año')->numeric()->minValue(1950)->maxValue(now()->year + 1),
                            Select::make('vehicle_type_id')->label('Tipo')->relationship('type', 'name')->preload(),
                            Select::make('transmission_id')->label('Transmisión')->relationship('transmission', 'name')->preload(),
                            Select::make('fuel_type_id')->label('Combustible')->relationship('fuel', 'name')->preload(),
                            TextInput::make('number_of_seats')->label('Asientos')->numeric()->minValue(1),
                            TextInput::make('number_of_doors')->label('Puertas')->numeric()->minValue(1),
                            TextInput::make('mileage')->label('Kilometraje')->numeric()->minValue(0)->suffix('km'),
                            TextInput::make('license_plate')->label('Placa')->maxLength(15),
                            TextInput::make('vin')->label('Chasis (VIN)')->maxLength(191)->columnSpan(['sm' => 2, 'xl' => 2]),
                        ]),

                    Section::make('Fotos')
                        ->description('La primera foto es la portada en la web. Arrástralas para cambiar el orden.')
                        ->schema([
                            FileUpload::make('images')
                                ->hiddenLabel()
                                ->disk('media')
                                ->directory('cars')
                                ->visibility('public')
                                ->image()
                                ->imageEditor()
                                ->multiple()
                                ->reorderable()
                                ->appendFiles()
                                ->panelLayout('grid')
                                ->imagePreviewHeight('170')
                                ->maxSize(8192),
                        ]),

                    Section::make('Descripción en la web')
                        ->collapsible()
                        ->schema([
                            Textarea::make('description')
                                ->label('Resumen')
                                ->helperText('Aparece debajo del nombre y en Google.')
                                ->rows(2)
                                ->maxLength(400)
                                ->hintAction(static::aiAction('summary')),
                            RichEditor::make('content')
                                ->label('Descripción completa')
                                ->hintAction(static::aiAction('content'))
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline'],
                                    ['h2', 'h3'],
                                    ['bulletList', 'orderedList'],
                                    ['undo', 'redo'],
                                ]),
                        ]),

                    TranslationSection::make([
                        'name' => ['label' => 'Nombre (English)'],
                        'description' => ['label' => 'Resumen (English)', 'type' => 'textarea', 'rows' => 2, 'max' => 400],
                        'content' => ['label' => 'Descripción completa (English)', 'type' => 'rich'],
                    ]),

                    SeoSection::make(withAi: true),
                ])->columnSpan(['default' => 3, 'lg' => 2]),

                Group::make([
                    Section::make('Aliado')
                        ->schema([
                            // Only a switch on the form: the owner is stored in owner_id.
                            Toggle::make('is_ally_car')
                                ->label('Vehículo aliado')
                                ->helperText('Un aliado presta este vehículo para que la empresa lo rente.')
                                ->dehydrated(false)
                                ->live()
                                ->afterStateHydrated(fn (Toggle $component, ?Car $record) => $component->state(filled($record?->owner_id)))
                                ->afterStateUpdated(function (bool $state, Set $set): void {
                                    if (! $state) {
                                        $set('owner_id', null);
                                    }
                                }),
                            Select::make('owner_id')
                                ->label('Aliado dueño')
                                ->options(fn () => User::query()->where('is_ally', true)->orderBy('first_name')->get()
                                    ->mapWithKeys(fn (User $ally) => [$ally->getKey() => $ally->getFilamentName()]))
                                ->searchable()
                                ->required()
                                ->visible(fn (Get $get) => (bool) $get('is_ally_car'))
                                ->helperText('Si no aparece, créalo en el módulo Aliados.'),
                        ]),
                    Section::make('Renta')
                        ->schema([
                            Select::make('status')
                                ->label('Estado')
                                ->options(CarStatus::class)
                                ->default(CarStatus::Available)
                                ->selectablePlaceholder(false)
                                ->required(),
                            TextInput::make('rental_rate')->label('Tarifa por día')->numeric()->minValue(0)->prefix('$')->required(),
                            TextInput::make('deposit_amount')->label('Depósito de garantía')->numeric()->minValue(0)->prefix('$'),
                            TextInput::make('min_rental_days')->label('Renta mínima')->numeric()->minValue(1)->default(1)->suffix('días')->required(),
                            Toggle::make('is_featured')->label('Destacado en la web'),
                        ]),
                    Section::make('Seguro')
                        ->schema([
                            Textarea::make('insurance_info')->hiddenLabel()->placeholder('Aseguradora, póliza, vencimiento...')->rows(3),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }
}
