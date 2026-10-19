<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends Resource
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'usuarios';

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'rol';

    protected static ?string $pluralModelLabel = 'roles';

    protected static ?int $navigationSort = 91;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre del rol')->placeholder('Recepción')->required()->unique(ignoreRecord: true)->maxLength(120),
                CheckboxList::make('modules')->label('Módulos permitidos')->options(Role::MODULES)->columns(2)->bulkToggleable()
                    ->helperText('El usuario con este rol solo verá estos módulos. Los super administradores y los usuarios sin rol ven todo.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Rol')->weight('bold')->searchable(),
                TextColumn::make('modules')->label('Módulos')->badge()
                    ->formatStateUsing(fn ($state) => Role::MODULES[$state] ?? $state)
                    ->limitList(3),
                TextColumn::make('users_count')->label('Usuarios')->counts('users'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
