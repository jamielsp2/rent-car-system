<?php

namespace App\Filament\Resources\Cars\Pages;

use App\Filament\Resources\Cars\CarResource;
use App\Models\Car;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Car $record
 */
class EditCar extends EditRecord
{
    protected static string $resource = CarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open')
                ->label('Ver en la web')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->slug)
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
            ViewAction::make(),
            // A car with rentals keeps its history: take it out of service instead.
            DeleteAction::make()
                ->visible(fn () => $this->record->bookingCars()->doesntExist())
                ->modalDescription('Se elimina el vehículo y su página en la web. Esta acción no se puede deshacer.'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Older paths may use Windows separators; the upload field needs them clean.
        $data['images'] = array_values(Car::packImages($data['images'] ?? []));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['images'] = Car::packImages($data['images'] ?? []);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncSlug($this->form->getRawState()['slug_key'] ?? null);
    }
}
