<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VehicleCondition: string implements HasLabel
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    public function getLabel(): string
    {
        return match ($this) {
            self::Excellent => 'Excelente',
            self::Good => 'Bueno',
            self::Fair => 'Regular',
            self::Poor => 'Malo',
        };
    }
}
