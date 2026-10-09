<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum FuelLevel: string implements HasLabel
{
    case Empty = 'empty';
    case Quarter = 'quarter';
    case Half = 'half';
    case ThreeQuarters = 'three_quarters';
    case Full = 'full';

    public function getLabel(): string
    {
        return match ($this) {
            self::Empty => 'Vacío',
            self::Quarter => '1/4',
            self::Half => '1/2',
            self::ThreeQuarters => '3/4',
            self::Full => 'Lleno',
        };
    }

    public function rank(): int
    {
        return array_search($this, self::cases(), true);
    }
}
