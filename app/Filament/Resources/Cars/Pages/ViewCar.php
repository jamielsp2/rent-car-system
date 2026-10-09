<?php

namespace App\Filament\Resources\Cars\Pages;

use App\Filament\Resources\Cars\CarResource;
use App\Models\Car;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * @property Car $record
 */
class ViewCar extends ViewRecord
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
            EditAction::make(),
        ];
    }
}
