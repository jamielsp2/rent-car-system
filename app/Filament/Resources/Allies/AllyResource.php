<?php

namespace App\Filament\Resources\Allies;

use App\Filament\Resources\Allies\Pages\CreateAlly;
use App\Filament\Resources\Allies\Pages\EditAlly;
use App\Filament\Resources\Allies\Pages\ListAllies;
use App\Filament\Resources\Allies\RelationManagers\CarsRelationManager;
use App\Filament\Resources\Allies\Schemas\AllyForm;
use App\Filament\Resources\Allies\Tables\AlliesTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Aliados: people who lend their cars to the rent-a-car. Each one signs in to
 * the /aliados panel and sees only the cars assigned here.
 */
class AllyResource extends Resource
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'aliados';

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHandRaised;

    protected static ?string $modelLabel = 'aliado';

    protected static ?string $pluralModelLabel = 'aliados';

    protected static ?string $recordTitleAttribute = 'first_name';

    protected static ?string $navigationLabel = 'Aliados';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_ally', true)->withCount('ownedCars');
    }

    public static function form(Schema $schema): Schema
    {
        return AllyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AlliesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CarsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAllies::route('/'),
            'create' => CreateAlly::route('/create'),
            'edit' => EditAlly::route('/{record}/edit'),
        ];
    }
}
