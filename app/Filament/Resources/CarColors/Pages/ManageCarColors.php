<?php

namespace App\Filament\Resources\CarColors\Pages;

use App\Filament\Resources\CarColors\CarColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCarColors extends ManageRecords
{
    protected static string $resource = CarColorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
