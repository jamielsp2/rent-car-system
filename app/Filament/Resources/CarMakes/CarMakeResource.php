<?php

namespace App\Filament\Resources\CarMakes;

use App\Filament\Resources\CarMakes\Pages\ManageCarMakes;
use App\Filament\Support\TranslationSection;
use App\Models\CarMake;
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

class CarMakeResource extends Resource
{
    protected static ?string $model = CarMake::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'marca';

    protected static ?string $pluralModelLabel = 'marcas';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->required()->maxLength(120)->unique(ignoreRecord: true),
                TextInput::make('order')->label('Orden')->numeric()->default(0)->minValue(0)->maxValue(127)->helperText('Menor número, primero en las listas.'),
                Select::make('status')->label('Estado')->options(['published' => 'Publicado', 'draft' => 'Borrador'])->default('published')->selectablePlaceholder(false)->required(),
                TranslationSection::make([
                    'name' => ['label' => 'Nombre (English)'],
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('name')->label('Marca')->weight('bold')->searchable()->sortable(),
                TextColumn::make('cars_count')->label('Vehículos')->counts('cars')->alignCenter(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (CarMake $record) => ! ($record->cars()->doesntExist())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCarMakes::route('/'),
        ];
    }
}
