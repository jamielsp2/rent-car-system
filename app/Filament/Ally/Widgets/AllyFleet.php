<?php

namespace App\Filament\Ally\Widgets;

use App\Filament\Widgets\Reports\Concerns\UsesReportPeriod;
use App\Models\Car;
use App\Services\AllyStats;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * One row per car of the ally: price, minimum stay, availability, renter and profit.
 */
class AllyFleet extends TableWidget
{
    use UsesReportPeriod;

    protected static ?string $heading = 'Vehículos';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        [$from, $to] = $this->period();
        $ownerId = auth()->id();
        $stats = $this->report()->fleet($from, $to, $ownerId)
            ->keyBy(fn (array $row) => $row['car']->getKey());
        $allies = app(AllyStats::class);

        return $table
            ->query(fn () => Car::query()->ownedBy($ownerId)->with('make')->orderBy('name'))
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Vehículo')->weight('bold')->searchable()
                    ->description(fn (Car $record) => $record->license_plate ?: null),
                TextColumn::make('rental_rate')->label('Precio por día')->alignEnd()
                    ->formatStateUsing(fn ($state) => Money::format($state)),
                TextColumn::make('min_rental_days')->label('Renta mínima')->suffix(' días')->alignCenter(),
                TextColumn::make('availability')->label('Situación')
                    ->state(fn (Car $record) => $allies->status($record)['label'])
                    ->badge()
                    ->color(fn (Car $record) => $allies->status($record)['color']),
                TextColumn::make('renter')->label('Quién la rentó / próxima renta')
                    ->state(fn (Car $record) => $allies->status($record)['rental']?->booking?->customer_name ?? '—')
                    ->description(function (Car $record) use ($allies) {
                        $rental = $allies->status($record)['rental'];

                        return $rental
                            ? $rental->rental_start_date->format('d/m/Y') . ' → ' . $rental->rental_end_date->format('d/m/Y')
                            : null;
                    }),
                TextColumn::make('income')->label('Ingresos')->alignEnd()->toggleable()
                    ->state(fn (Car $record) => $stats[$record->getKey()]['income'] ?? 0)
                    ->formatStateUsing(fn ($state) => Money::format($state)),
                TextColumn::make('expenses')->label('Gastos')->alignEnd()->toggleable()
                    ->state(fn (Car $record) => $stats[$record->getKey()]['expenses'] ?? 0)
                    ->formatStateUsing(fn ($state) => Money::format($state)),
                TextColumn::make('profit')->label('Rentabilidad')->alignEnd()->weight('bold')
                    ->state(fn (Car $record) => $stats[$record->getKey()]['profit'] ?? 0)
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('utilization')->label('Ocupación')->alignCenter()->toggleable()
                    ->state(fn (Car $record) => round($stats[$record->getKey()]['utilization'] ?? 0))
                    ->suffix('%'),
                TextColumn::make('rentals')->label('Rentas')->alignCenter()->toggleable()
                    ->state(fn (Car $record) => $stats[$record->getKey()]['rentals'] ?? 0),
            ])
            ->recordActions([
                Action::make('history')
                    ->label('Historial')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading(fn (Car $record) => 'Historial de ' . $record->name)
                    ->modalContent(fn (Car $record) => view('filament.ally.car-history', [
                        'rentals' => $allies->history($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
            ]);
    }
}
