<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ServicePriceType: string implements HasLabel
{
    case Once = 'once';
    case PerDay = 'per_day';

    public function getLabel(): string
    {
        return match ($this) {
            self::Once => 'Una vez por reserva',
            self::PerDay => 'Por día',
        };
    }
}
