<?php

namespace App\Filament\Resources\Cars\Schemas;

use App\Models\Car;
use App\Support\Media;
use App\Support\LocalTime;
use App\Support\Money;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CarInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $money = fn ($state) => Money::format($state);
        $yearDays = LocalTime::now()->daysInYear;

        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Fotos')
                        ->schema([
                            ImageEntry::make('photos')
                                ->hiddenLabel()
                                ->state(fn (Car $record) => collect($record->images)->map(fn ($path) => Media::url($path))->values()->all())
                                ->imageHeight(140)
                                ->extraImgAttributes(['class' => 'rounded-md object-cover'])
                                ->placeholder('Sin fotos. Súbelas desde Editar.'),
                        ]),

                    Section::make('Vehículo')
                        ->columns(['default' => 2, 'xl' => 3])
                        ->schema([
                            TextEntry::make('make.name')->label('Marca')->placeholder('—'),
                            TextEntry::make('model_name')->label('Modelo')->placeholder('—'),
                            TextEntry::make('year')->label('Año')->placeholder('—'),
                            TextEntry::make('type.name')->label('Tipo')->placeholder('—'),
                            TextEntry::make('transmission.name')->label('Transmisión')->placeholder('—'),
                            TextEntry::make('fuel.name')->label('Combustible')->placeholder('—'),
                            TextEntry::make('number_of_seats')->label('Asientos')->placeholder('—'),
                            TextEntry::make('number_of_doors')->label('Puertas')->placeholder('—'),
                            TextEntry::make('mileage')->label('Kilometraje')->numeric()->suffix(' km')->placeholder('—'),
                            TextEntry::make('license_plate')->label('Placa')->placeholder('—'),
                            TextEntry::make('vin')->label('Chasis (VIN)')->placeholder('—')->columnSpan(2),
                        ]),

                    Section::make('Rentabilidad ' . LocalTime::now()->year)
                        ->description('Renta sin ITBIS ni servicios, de reservas confirmadas, entregadas o completadas, menos los gastos del vehículo.')
                        ->columns(['default' => 2, 'xl' => 4])
                        ->schema([
                            TextEntry::make('year_income')->label('Ingresos')->formatStateUsing($money)->size('lg'),
                            TextEntry::make('year_expenses')->label('Gastos')->formatStateUsing($money)->color('danger')->size('lg'),
                            TextEntry::make('year_profit')
                                ->label('Rentabilidad')
                                ->formatStateUsing($money)
                                ->size('lg')
                                ->weight('bold')
                                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                            TextEntry::make('year_rented_days')
                                ->label('Ocupación')
                                ->size('lg')
                                ->formatStateUsing(fn ($state) => round($state / $yearDays * 100, 1) . '%')
                                ->helperText(fn ($state) => (int) $state . ' de ' . $yearDays . ' días'),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 2]),

                Group::make([
                    Section::make('Renta')
                        ->schema([
                            TextEntry::make('status')->label('Estado')->badge(),
                            TextEntry::make('rental_rate')->label('Tarifa por día')->formatStateUsing($money)->size('lg')->weight('bold'),
                            TextEntry::make('deposit_amount')->label('Depósito de garantía')->formatStateUsing($money)->placeholder('—'),
                            TextEntry::make('min_rental_days')->label('Renta mínima')->suffix(' días'),
                            IconEntry::make('is_featured')->label('Destacado en la web')->boolean(),
                            TextEntry::make('booking_cars_count')->label('Rentas registradas'),
                        ]),
                    Section::make('Seguro')
                        ->schema([
                            TextEntry::make('insurance_info')->hiddenLabel()->placeholder('Sin datos del seguro.'),
                        ]),
                ])->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }
}
