<?php

namespace App\Filament\Resources\CarFuels\Pages;

use App\Filament\Resources\CarFuels\CarFuelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarFuels extends ManageRecords
{
    protected static string $resource = CarFuelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
