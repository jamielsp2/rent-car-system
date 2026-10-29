<?php

namespace App\Filament\Resources\Allies\RelationManagers;

use App\Models\Car;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Vehicles lent by this ally. Assign a car here, or flag it from the car itself.
 */
class CarsRelationManager extends RelationManager
{
    protected static string $relationship = 'ownedCars';

    protected static ?string $title = 'Vehículos del aliado';

    protected static ?string $modelLabel = 'vehículo';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Vehículo')->weight('bold')->searchable(),
                TextColumn::make('license_plate')->label('Placa')->placeholder('—'),
                TextColumn::make('rental_rate')->label('Tarifa por día')->formatStateUsing(fn ($state) => Money::format($state))->alignEnd(),
                TextColumn::make('min_rental_days')->label('Renta mínima')->suffix(' días'),
                TextColumn::make('status')->label('Estado')->badge(),
            ])
            ->headerActions([
                Action::make('assignCar')
                    ->label('Asignar vehículo')
                    ->icon(Heroicon::OutlinedPlus)
                    ->schema([
                        Select::make('car_id')
                            ->label('Vehículo')
                            ->options(fn () => Car::query()
                                ->where(fn ($q) => $q->whereNull('owner_id')->orWhere('owner_id', '!=', $this->getOwnerRecord()->getKey()))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (Car $car) => [$car->getKey() => $car->name . ($car->owner_id ? ' (de otro aliado)' : ' (propio de la empresa)')]))
                            ->searchable()
                            ->required()
                            ->helperText('Un vehículo pertenece a un solo aliado.'),
                    ])
                    ->action(function (array $data): void {
                        Car::query()->whereKey($data['car_id'])->update(['owner_id' => $this->getOwnerRecord()->getKey()]);

                        Notification::make()->success()->title('Vehículo asignado')->send();
                    }),
            ])
            ->recordActions([
                Action::make('unassign')
                    ->label('Quitar')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('El vehículo vuelve a ser propio de la empresa. Sus reservas y gastos se conservan.')
                    ->action(fn (Car $record) => $record->update(['owner_id' => null])),
            ]);
    }
}
