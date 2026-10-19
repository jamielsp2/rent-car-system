<?php

namespace App\Filament\Resources\CarTypes;

use App\Filament\Resources\CarTypes\Pages\ManageCarTypes;
use App\Filament\Support\TranslationSection;
use App\Models\CarType;
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

class CarTypeResource extends Resource
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'catalogos';

    protected static ?string $model = CarType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    protected static ?string $modelLabel = 'tipo de vehículo';

    protected static ?string $pluralModelLabel = 'tipos de vehículo';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Nombre')->placeholder('SUV, Sedán, Pickup...')->required()->maxLength(191)->unique(ignoreRecord: true),
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
                TextColumn::make('name')->label('Tipo')->weight('bold')->searchable()->sortable(),
                TextColumn::make('cars_count')->label('Vehículos')->counts('cars')->alignCenter(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn ($state) => $state === 'published' ? 'Publicado' : 'Borrador')->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (CarType $record) => ! ($record->cars()->doesntExist())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCarTypes::route('/'),
        ];
    }
}
