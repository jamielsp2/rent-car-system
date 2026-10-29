<?php

namespace App\Filament\Resources\Allies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AllyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Datos del aliado')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')->label('Nombre')->required()->maxLength(120),
                        TextInput::make('last_name')->label('Apellido')->maxLength(120),
                        TextInput::make('email')->label('Correo')->email()->required()->unique(ignoreRecord: true)->maxLength(255)
                            ->helperText('Con este correo entra al panel de aliados.'),
                        TextInput::make('username')->label('Usuario')->unique(ignoreRecord: true)->maxLength(60),
                        TextInput::make('phone')->label('Teléfono')->tel()->maxLength(30),
                        TextInput::make('password')->label('Contraseña')->password()->revealable()
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->minLength(8)
                            ->helperText('Al editar, déjala vacía para conservar la actual.'),
                    ]),
            ]);
    }
}
