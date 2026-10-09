<?php

namespace App\Filament\Resources\CarFuels;

use App\Filament\Resources\CarFuels\Pages\ManageCarFuels;
use App\Models\CarFuel;
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

class CarFuelResource extends Resource
{
    protected static ?string $model = CarFuel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'combustible';

    protected static ?string $pluralModelLabel = 'combustibles';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->placeholder('Gasolina, Diésel...')->required()->maxLength(191)->unique(ignoreRecord: true),
                TextInput::make('order')->label('Orden')->numeric()->default(0)->minValue(0)->maxValue(127)->helperText('Menor número, primero en las listas.'),
                Select::make('status')->label('Estado')->options(['published' => 'Publicado', 'draft' => 'Borrador'])->default('published')->selectablePlaceholder(false)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('name')->label('Combustible')->weight('bold')->searchable()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (CarFuel $record) => ! (! \App\Models\Car::query()->where('fuel_type_id', $record->getKey())->exists())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCarFuels::route('/'),
        ];
    }
}
