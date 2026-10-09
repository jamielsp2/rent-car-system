<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CustomerStatus: string implements HasColor, HasLabel
{
    case Activated = 'activated';
    case Locked = 'locked';
    case Blocked = 'blocked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Activated => 'Activo',
            self::Locked => 'Bloqueado temporalmente',
            self::Blocked => 'Bloqueado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Activated => 'success',
            self::Locked => 'warning',
            self::Blocked => 'danger',
        };
    }
}
