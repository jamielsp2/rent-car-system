<?php

namespace App\Filament\Resources\CarTypes\Pages;

use App\Filament\Resources\CarTypes\CarTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarTypes extends ManageRecords
{
    protected static string $resource = CarTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
