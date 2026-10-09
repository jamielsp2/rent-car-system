<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            // A customer with bookings keeps their history: block the account instead.
            DeleteAction::make()
                ->visible(fn () => $this->record->bookings()->doesntExist())
                ->modalDescription('Se elimina el cliente. Solo es posible si no tiene reservas.'),
        ];
    }
}
