<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\FuelLevel;
use App\Models\Booking;
use App\Support\Media;
use App\Support\Money;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * Read-only booking details, shown in the left column of the booking page.
 */
class BookingInfolist
{
    public static function detailSections(): array
    {
        $money = fn ($state) => Money::format($state);

        return [
            Section::make('Información de la reserva')
                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('booking_number')->label('Número de reserva')->weight('bold'),
                    TextEntry::make('created_at')->label('Creada')->dateTime('d/m/Y h:i A'),
                    TextEntry::make('status')->label('Estado')->badge(),

                    TextEntry::make('customer_name')->label('Nombre completo'),
                    TextEntry::make('customer_email')->label('Correo electrónico')->placeholder('—'),
                    TextEntry::make('customer_phone')->label('Teléfono')->placeholder('—'),

                    ImageEntry::make('car_image')
                        ->label('Vehículo')
                        ->state(fn (Booking $record) => $record->rental?->car?->cover_url ?? Media::url($record->rental?->car_image))
                        ->imageHeight(90),
                    TextEntry::make('rental.car_name')->label('Nombre'),
                    TextEntry::make('rental.car.license_plate')->label('Placa')->placeholder('—'),

                    TextEntry::make('rental.rental_start_date')->label('Fecha de salida')->dateTime('d/m/Y h:i A'),
                    TextEntry::make('rental.rental_end_date')->label('Fecha de devolución')->dateTime('d/m/Y h:i A'),
                    TextEntry::make('rental_days')->label('Días')->state(fn (Booking $record) => $record->rental?->days()),

                    TextEntry::make('services.name')
                        ->label('Servicios adicionales')
                        ->listWithLineBreaks()
                        ->bulleted()
                        ->placeholder('Ninguno')
                        ->columnSpanFull(),
                    TextEntry::make('note')->label('Nota del cliente')->placeholder('—')->columnSpanFull(),
                ]),

            Section::make('Importes y pago')
                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('sub_total')->label('Subtotal')->formatStateUsing($money),
                    TextEntry::make('tax_amount')->label('Impuestos')->formatStateUsing($money),
                    TextEntry::make('amount')->label('Total')->formatStateUsing($money)->weight('bold'),
                    TextEntry::make('coupon_amount')->label('Descuento')->formatStateUsing($money),
                    TextEntry::make('credit_amount')->label('Crédito usado')->formatStateUsing($money),
                    TextEntry::make('deposit_amount')->label('Depósito')->formatStateUsing($money),
                    TextEntry::make('payment.payment_channel')->label('Método de pago')->placeholder('—'),
                    TextEntry::make('payment.charge_id')->label('ID del pago')->placeholder('—'),
                    TextEntry::make('payment.status')->label('Estado del pago')->badge()->placeholder('Sin pago'),
                ]),

            Section::make('Contrato, entrega y devolución')
                ->columns(['default' => 1, 'sm' => 2, 'md' => 3, 'lg' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('contract.signed_at')->label('Entregado (contrato firmado)')->dateTime('d/m/Y h:i A')->placeholder('Sin firmar'),
                    TextEntry::make('contract.pickup_mileage')->label('Km de salida')->numeric()->placeholder('—'),
                    TextEntry::make('contract.returned_at')->label('Devuelto')->dateTime('d/m/Y h:i A')->placeholder('Pendiente'),
                    TextEntry::make('contract_driven')
                        ->label('Km recorridos')
                        ->state(fn (Booking $record) => $record->contract?->drivenDistance())
                        ->numeric()
                        ->placeholder('—'),
                    TextEntry::make('completion_gas_level')
                        ->label('Combustible a la llegada')
                        ->formatStateUsing(fn ($state) => FuelLevel::tryFrom((string) $state)?->getLabel() ?? $state)
                        ->placeholder('—'),
                    TextEntry::make('contract.return_extra_charges')->label('Cargos adicionales')->formatStateUsing($money)->placeholder('—'),
                    TextEntry::make('completion_notes')->label('Notas de la devolución')->placeholder('—')->columnSpanFull(),
                ]),
        ];
    }
}
