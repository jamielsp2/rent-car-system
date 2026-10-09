<?php

namespace App\Filament\Resources\CarTransmissions\Pages;

use App\Filament\Resources\CarTransmissions\CarTransmissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarTransmissions extends ManageRecords
{
    protected static string $resource = CarTransmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
