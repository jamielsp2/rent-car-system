<?php

namespace App\Filament\Resources\Cars\Pages;

use App\Filament\Resources\Cars\CarResource;
use App\Models\Car;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Car $record
 */
class CreateCar extends CreateRecord
{
    protected static string $resource = CarResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['images'] = Car::packImages($data['images'] ?? []);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncSlug($this->form->getRawState()['slug_key'] ?? null);
    }

    protected function getRedirectUrl(): string
    {
        return CarResource::getUrl('view', ['record' => $this->record]);
    }
}
