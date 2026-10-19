<?php

namespace App\Filament\Resources\Taxes;

use App\Filament\Resources\Taxes\Pages\ManageTaxes;
use App\Filament\Support\TranslationSection;
use App\Models\Tax;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class TaxResource extends Resource
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'catalogos';

    protected static ?string $model = Tax::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'impuesto';

    protected static ?string $pluralModelLabel = 'impuestos';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->placeholder('ITBIS')->required()->maxLength(120),
                TextInput::make('percentage')->label('Porcentaje')->numeric()->minValue(0)->maxValue(100)->default(0)->suffix('%')->required()->helperText('Se aplica sobre la renta y los servicios adicionales.'),
                TextInput::make('amount')->label('Monto fijo')->numeric()->minValue(0)->default(0)->prefix('$')->helperText('Opcional: un monto fijo que se suma además del porcentaje.'),
                Select::make('amount_type')->label('El monto fijo se cobra')->options(['once' => 'Una vez por reserva', 'per_day' => 'Por cada día'])->default('once')->selectablePlaceholder(false)->required(),
                TextInput::make('priority')->label('Orden')->numeric()->default(1)->minValue(0),
                Select::make('status')->label('Estado')->options(['published' => 'Se aplica en las reservas', 'draft' => 'No se aplica'])->default('published')->selectablePlaceholder(false)->required(),
                TranslationSection::make([
                    'name' => ['label' => 'Nombre (English)'],
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority')
            ->columns([
                TextColumn::make('name')->label('Impuesto')->weight('bold')->searchable(),
                TextColumn::make('percentage')->label('Porcentaje')->suffix('%'),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Se aplica' : 'No se aplica')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (Tax $record) => ! (true)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTaxes::route('/'),
        ];
    }
}
