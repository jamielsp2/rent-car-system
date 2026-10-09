<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Support\Money;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('booking_number')
                    ->label('Reserva')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->description(fn (Booking $record) => $record->customer_phone)
                    ->searchable(['customer_name', 'customer_email', 'customer_phone']),
                TextColumn::make('rental.car_name')
                    ->label('Vehículo')
                    ->wrap()
                    ->limit(40),
                TextColumn::make('rental.rental_start_date')
                    ->label('Período de alquiler')
                    ->formatStateUsing(fn ($state) => $state?->format('d/m/Y H:i'))
                    ->description(fn (Booking $record) => '→ ' . $record->rental?->rental_end_date?->format('d/m/Y H:i')),
                TextColumn::make('amount')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->alignEnd(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('payment.status')
                    ->label('Pago')
                    ->visibleFrom('xl')
                    ->badge(),
                IconColumn::make('contract_state')
                    ->label('Contrato')
                    ->visibleFrom('xl')
                    ->state(fn (Booking $record) => match (true) {
                        (bool) $record->contract?->isReturned() => 'returned',
                        (bool) $record->contract?->isSigned() => 'signed',
                        default => 'none',
                    })
                    ->icon(fn (string $state) => match ($state) {
                        'returned' => 'heroicon-o-check-badge',
                        'signed' => 'heroicon-o-document-check',
                        default => 'heroicon-o-minus',
                    })
                    ->color(fn (string $state) => match ($state) {
                        'returned' => 'success',
                        'signed' => 'primary',
                        default => 'gray',
                    })
                    ->tooltip(fn (string $state) => match ($state) {
                        'returned' => 'Devuelto',
                        'signed' => 'Contrato firmado',
                        default => 'Sin contrato',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(BookingStatus::class)
                    ->multiple(),
                SelectFilter::make('payment_status')
                    ->label('Estado del pago')
                    ->options(PaymentStatus::class)
                    ->query(fn (Builder $query, array $data) => $data['value']
                        ? $query->whereHas('payment', fn (Builder $q) => $q->where('status', $data['value']))
                        : $query),
            ])
            ->recordActions([
                EditAction::make()->iconButton(),
            ]);
    }
}
