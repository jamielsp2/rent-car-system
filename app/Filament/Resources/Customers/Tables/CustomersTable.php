<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerStatus;
use App\Enums\LicenseStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('Cliente')
                    ->description(fn ($record) => $record->email)
                    ->searchable(['name', 'email', 'phone'])
                    ->sortable(),
                TextColumn::make('phone')->label('Teléfono')->placeholder('—'),
                TextColumn::make('license_status')->label('Licencia')->badge()->placeholder('Sin enviar')->visibleFrom('xl'),
                TextColumn::make('status')->label('Estado')->badge(),
                TextColumn::make('bookings_count')->label('Reservas')->sortable()->alignCenter(),
                TextColumn::make('created_at')->label('Registrado')->date('d/m/Y')->sortable()->visibleFrom('xl'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Estado')->options(CustomerStatus::class),
                SelectFilter::make('license_status')->label('Licencia')->options(LicenseStatus::class),
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ]);
    }
}
