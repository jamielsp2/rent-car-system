<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Pick one vehicle from a paged list. Each row has its own "Ver fotos" button.
 *
 * `vehicles` receives a list of ['id', 'name', 'make', 'price', 'cover', 'photos'].
 * The selected id is stored in the field state.
 */
class VehiclePicker extends Field
{
    protected string $view = 'filament.bookings.vehicle-picker';

    /** @var array<int, array<string, mixed>>|Closure */
    protected array|Closure $vehicles = [];

    /**
     * @param  array<int, array<string, mixed>>|Closure  $vehicles
     */
    public function vehicles(array|Closure $vehicles): static
    {
        $this->vehicles = $vehicles;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVehicles(): array
    {
        return array_values($this->evaluate($this->vehicles) ?? []);
    }
}
