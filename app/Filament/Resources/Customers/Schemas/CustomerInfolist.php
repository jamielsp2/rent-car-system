<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Customer;
use App\Support\Media;
use App\Support\Money;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Cliente')
                    ->columnSpan(2)
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nombre')->weight('bold'),
                        TextEntry::make('status')->label('Estado')->badge(),
                        TextEntry::make('email')->label('Correo'),
                        TextEntry::make('phone')->label('Teléfono')->placeholder('—'),
                        TextEntry::make('address')->label('Dirección')->placeholder('—'),
                        TextEntry::make('created_at')->label('Registrado')->date('d/m/Y'),
                        TextEntry::make('block_reason')->label('Motivo del bloqueo')->placeholder('—')->columnSpanFull(),
                    ]),

                Section::make('Licencia de conducir')
                    ->columnSpan(1)
                    ->schema([
                        TextEntry::make('license_status')->label('Revisión')->badge()->placeholder('Sin enviar'),
                        TextEntry::make('license_number')->label('Número')->placeholder('—'),
                        TextEntry::make('license_expiry_date')->label('Vence')->date('d/m/Y')->placeholder('—'),
                        ImageEntry::make('license_images')
                            ->label('Fotos')
                            ->state(fn (Customer $record) => array_values(array_filter([
                                Media::url($record->license_front_image),
                                Media::url($record->license_back_image),
                            ])))
                            ->imageHeight(80),
                    ]),

                Section::make('Reservas')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('bookings')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('booking_number')
                                    ->label('Reserva')
                                    ->url(fn ($record) => BookingResource::getUrl('edit', ['record' => $record])),
                                TextEntry::make('rental.car_name')->label('Vehículo'),
                                TextEntry::make('rental.rental_start_date')->label('Salida')->date('d/m/Y'),
                                TextEntry::make('amount')->label('Total')->formatStateUsing(fn ($state) => Money::format($state)),
                                TextEntry::make('status')->label('Estado')->badge(),
                            ])
                            ->placeholder('Sin reservas'),
                    ]),
            ]);
    }
}
