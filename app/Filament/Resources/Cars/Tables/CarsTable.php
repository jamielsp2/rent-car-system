<?php

namespace App\Filament\Resources\Cars\Tables;

use App\Enums\CarStatus;
use App\Models\BookingCar;
use App\Models\Car;
use App\Support\LocalTime;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CarsTable
{
    public static function configure(Table $table): Table
    {
        // Year the money and occupancy columns refer to, chosen in the filters.
        $year = fn ($livewire): int => (int) ($livewire->tableFilters['year']['value'] ?? LocalTime::now()->year);
        $yearDays = fn ($livewire): int => CarbonImmutable::create($year($livewire))->daysInYear;

        return $table
            ->query(fn ($livewire) => Car::query()
                ->with(['make', 'type', 'transmission', 'fuel', 'slug'])
                ->withCount('bookingCars')
                ->withYearStats($year($livewire)))
            ->defaultSort('order')
            ->columns([
                ImageColumn::make('cover_url')
                    ->label('')
                    ->state(fn (Car $record) => $record->cover_url)
                    ->imageWidth(72)
                    ->imageHeight(48)
                    ->extraImgAttributes(['class' => 'rounded-md object-cover ring-1 ring-gray-950/5']),
                TextColumn::make('name')
                    ->label('Vehículo')
                    ->description(fn (Car $record) => collect([$record->make?->name, $record->year ?: null, $record->license_plate])->filter()->implode(' · '))
                    ->weight('medium')
                    ->searchable(['name', 'license_plate'])
                    ->sortable()
                    ->wrap(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('license_plate')
                    ->label('Placa')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('year')
                    ->label('Año')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('rental_rate')
                    ->label('Tarifa / día')
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('booking_cars_count')
                    ->label('Rentas')
                    ->sortable()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                // Profitability of the chosen year (see Car::scopeWithYearStats).
                TextColumn::make('year_income')
                    ->label(fn ($livewire) => 'Ingresos ' . $year($livewire))
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->tooltip('Renta sin ITBIS ni servicios, de reservas confirmadas, entregadas o completadas.')
                    ->sortable()
                    ->visibleFrom('xl')
                    ->alignEnd(),
                TextColumn::make('year_expenses')
                    ->label(fn ($livewire) => 'Gastos ' . $year($livewire))
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->sortable()
                    ->visibleFrom('xl')
                    ->alignEnd(),
                TextColumn::make('year_profit')
                    ->label(fn ($livewire) => 'Rentabilidad ' . $year($livewire))
                    ->formatStateUsing(fn ($state) => Money::format($state))
                    ->description(fn (Car $record) => $record->year_income > 0
                        ? 'Margen ' . round($record->year_profit / $record->year_income * 100) . '%'
                        : null)
                    ->color(fn ($state) => match (true) {
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->weight('bold')
                    ->sortable()
                    ->alignEnd(),
                TextColumn::make('year_rented_days')
                    ->label(fn ($livewire) => 'Ocupación ' . $year($livewire))
                    ->formatStateUsing(fn ($state, $livewire) => round($state / $yearDays($livewire) * 100, 1) . '%')
                    ->description(fn ($state, $livewire) => (int) $state . ' de ' . $yearDays($livewire) . ' días')
                    ->sortable()
                    ->visibleFrom('xl')
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Año de los ingresos')
                    ->options(fn () => static::years())
                    ->default((string) LocalTime::now()->year)
                    ->selectablePlaceholder(false)
                    // Applied in the table query above; nothing to filter out.
                    ->query(fn (Builder $query) => $query)
                    ->indicateUsing(fn () => null), // The year already shows in the column headers.
                SelectFilter::make('status')->label('Estado')->options(CarStatus::class),
                SelectFilter::make('make_id')->label('Marca')->relationship('make', 'name'),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(['default' => 1, 'sm' => 3])
            ->emptyStateHeading('Todavía no hay vehículos')
            ->emptyStateDescription('Agrega el primero con el botón «Nuevo vehículo».')
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton(),
            ]);
    }

    /**
     * From the first rental on record to next year, newest first.
     *
     * @return array<string, string>
     */
    protected static function years(): array
    {
        $current = LocalTime::now()->year;
        $first = (int) (BookingCar::query()->min('rental_start_date') ? substr(BookingCar::query()->min('rental_start_date'), 0, 4) : $current);

        return collect(range($current + 1, min($first, $current)))
            ->mapWithKeys(fn (int $y) => [(string) $y => (string) $y])
            ->all();
    }
}
