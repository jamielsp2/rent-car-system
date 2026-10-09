<?php

namespace App\Filament\Resources\CarTransmissions;

use App\Filament\Resources\CarTransmissions\Pages\ManageCarTransmissions;
use App\Models\CarTransmission;
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

class CarTransmissionResource extends Resource
{
    protected static ?string $model = CarTransmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'transmisión';

    protected static ?string $pluralModelLabel = 'transmisiones';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->placeholder('Automática, Manual...')->required()->maxLength(191)->unique(ignoreRecord: true),
                TextInput::make('order')->label('Orden')->numeric()->default(0)->minValue(0)->maxValue(127)->helperText('Menor número, primero en las listas.'),
                Select::make('status')->label('Estado')->options(['published' => 'Publicado', 'draft' => 'Borrador'])->default('published')->selectablePlaceholder(false)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('name')->label('Transmisión')->weight('bold')->searchable()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (CarTransmission $record) => ! (! \App\Models\Car::query()->where('transmission_id', $record->getKey())->exists())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCarTransmissions::route('/'),
        ];
    }
}
