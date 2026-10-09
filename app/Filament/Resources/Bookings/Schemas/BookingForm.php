<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Booking details on the left, status
 * and payment cards on the right.
 */
class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make(BookingInfolist::detailSections())
                    ->columnSpan(['default' => 3, 'lg' => 2]),

                Group::make([
                    Section::make('Publicar')
                        ->schema([
                            Actions::make([
                                Action::make('saveBooking')
                                    ->label('Guardar')
                                    ->icon(Heroicon::OutlinedCheck)
                                    ->submit('save'),
                            ]),
                        ]),

                    Section::make('Estatus')
                        ->schema([
                            Select::make('status')
                                ->hiddenLabel()
                                ->options(BookingStatus::class)
                                ->required()
                                ->selectablePlaceholder(false)
                                ->helperText('Para marcar "Entregado (Renta iniciada)" el contrato debe estar firmado y tener sus fotos. Una renta entregada se completa firmando la revisión de devolución.'),
                        ]),

                    Section::make('Estado del pago')
                        ->visible(fn (?Booking $record) => (bool) $record?->payment)
                        ->schema([
                            Select::make('payment_status')
                                ->hiddenLabel()
                                ->options(PaymentStatus::class)
                                ->selectablePlaceholder(false)
                                ->afterStateHydrated(fn (Select $component, ?Booking $record) => $component->state($record?->payment?->status))
                                ->dehydrated()
                                ->helperText('Estado actual del pago. La factura se actualiza automáticamente.'),
                        ]),
                ])
                    // Save and status come first on phones and tablets (side by side on
                    // tablets) instead of waiting at the end of a long page.
                    ->columns(['default' => 1, 'sm' => 3, 'lg' => 1])
                    ->extraAttributes(['class' => 'vd-booking-side'])
                    ->columnSpan(['default' => 3, 'lg' => 1]),
            ]);
    }
}
