<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\Actions\CreateBookingAction;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function mount(): void
    {
        parent::mount();

        // The top-bar "Nueva reserva" link opens the form right away.
        if (request()->boolean('nueva')) {
            $this->mountAction('newBooking');
        }
    }

    /**
     * Declared as a method so Filament can find the action on every Livewire request
     * (typing in the form makes new requests; a header action would be lost).
     */
    protected function newBookingAction(): Action
    {
        return CreateBookingAction::make();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('nuevaReserva')
                ->label('Nueva reserva')
                ->icon(Heroicon::OutlinedPlus)
                ->action(fn () => $this->mountAction('newBooking')),
        ];
    }
}
