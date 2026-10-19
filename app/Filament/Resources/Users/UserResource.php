<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    use \App\Filament\Concerns\RestrictedByRole;

    protected static string $module = 'usuarios';

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?int $navigationSort = 90;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('first_name')->label('Nombre')->required()->maxLength(120),
                TextInput::make('last_name')->label('Apellido')->maxLength(120),
                TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('username')->label('Usuario')->unique(ignoreRecord: true)->maxLength(60),
                TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                TextInput::make('password')->label('Contraseña')->password()->revealable()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->minLength(8)
                    ->helperText('Al editar, déjala vacía para conservar la actual.'),
                Toggle::make('super_user')->label('Super administrador')->default(false),
                Select::make('role_id')->label('Rol')->relationship('role', 'name')->placeholder('Sin rol (acceso completo)')->helperText('Limita los módulos que ve este usuario. Se ignora si es super administrador.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')->label('Nombre')->weight('bold')->searchable()
                    ->formatStateUsing(fn ($state, User $record) => $record->getFilamentName()),
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('phone')->label('Teléfono'),
                IconColumn::make('super_user')->label('Super admin')->boolean(),
                TextColumn::make('role.name')->label('Rol')->badge()->placeholder('Acceso completo'),
                TextColumn::make('last_login')->label('Último acceso')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user())),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
