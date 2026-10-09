<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CarStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Rented = 'rented';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Rented => 'Rentado',
            self::Maintenance => 'En mantenimiento',
            self::OutOfService => 'Fuera de servicio',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Rented => 'primary',
            self::Maintenance => 'warning',
            self::OutOfService => 'danger',
        };
    }
}
