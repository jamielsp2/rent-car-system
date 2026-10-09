<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Damage marks placed on the vehicle diagram.
 */
enum DamageType: string implements HasColor, HasLabel
{
    case Scratch = 'scratch';
    case Dent = 'dent';
    case Hit = 'hit';
    case Chip = 'chip';
    case Broken = 'broken';
    case Missing = 'missing';

    public function getLabel(): string
    {
        return match ($this) {
            self::Scratch => 'Rayón',
            self::Dent => 'Abolladura',
            self::Hit => 'Golpe',
            self::Chip => 'Picadura (cristal)',
            self::Broken => 'Roto / quebrado',
            self::Missing => 'Faltante',
        };
    }

    public function code(): string
    {
        return match ($this) {
            self::Scratch => 'R',
            self::Dent => 'A',
            self::Hit => 'G',
            self::Chip => 'P',
            self::Broken => 'Q',
            self::Missing => 'F',
        };
    }

    public function getColor(): string
    {
        return $this->hex();
    }

    public function hex(): string
    {
        return match ($this) {
            self::Scratch => '#d97706',
            self::Dent => '#2563eb',
            self::Hit => '#dc2626',
            self::Chip => '#0891b2',
            self::Broken => '#7c3aed',
            self::Missing => '#059669',
        };
    }
}
