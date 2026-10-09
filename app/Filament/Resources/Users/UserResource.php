<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Staff who can sign in to the admin panel. Everyone has full access.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $modelLabel = 'usuario';

    protected static ?string $pluralModelLabel = 'usuarios';

    protected static ?int $navigationSort = 80;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('first_name')->label('Nombre')->required()->maxLength(120),
                TextInput::make('last_name')->label('Apellido')->maxLength(120),
                TextInput::make('email')->label('Correo')->email()->required()->maxLength(191)->unique(ignoreRecord: true),
                TextInput::make('phone')->label('Teléfono')->tel()->maxLength(20),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required(fn (?User $record) => ! $record)
                    ->minLength(8)
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (?User $record) => $record ? 'Déjala vacía para conservar la actual.' : 'Mínimo 8 caracteres.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Usuario')
                    ->weight('bold')
                    ->state(fn (User $record) => $record->getFilamentName())
                    ->description(fn (User $record) => $record->email)
                    ->searchable(['first_name', 'last_name', 'email']),
                TextColumn::make('phone')->label('Teléfono')->placeholder('—'),
                TextColumn::make('last_login')->label('Último acceso')->dateTime('d/m/Y h:i A')->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),
                // You can't delete yourself, and the last account can't go.
                DeleteAction::make()->hidden(fn (User $record) => $record->is(auth()->user()) || User::query()->count() <= 1),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
