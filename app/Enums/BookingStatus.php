<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Rental lifecycle. Values match what is stored in cr_bookings.status.
 */
enum BookingStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'processing';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Confirmed => 'Confirmada',
            self::Delivered => 'Entregado (Renta iniciada)',
            self::Completed => 'Completada',
            self::Cancelled => 'Cancelada',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'info',
            self::Delivered => 'primary',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * Statuses that keep the vehicle occupied for availability purposes.
     */
    public static function occupying(): array
    {
        return [self::Pending, self::Confirmed, self::Delivered, self::Completed];
    }
}
