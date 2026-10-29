<?php

namespace App\Filament\Resources\Allies\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AlliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('first_name')
            ->columns([
                TextColumn::make('first_name')->label('Aliado')->weight('bold')->searchable(['first_name', 'last_name', 'email'])
                    ->formatStateUsing(fn ($state, User $record) => $record->getFilamentName())
                    ->description(fn (User $record) => $record->email),
                TextColumn::make('phone')->label('Teléfono')->placeholder('—'),
                TextColumn::make('owned_cars_count')->label('Vehículos')->alignCenter(),
                TextColumn::make('last_login')->label('Último acceso')->dateTime('d/m/Y H:i')->placeholder('Nunca')->visibleFrom('lg'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Se elimina el aliado. Sus vehículos quedan como propios de la empresa.'),
            ]);
    }
}
