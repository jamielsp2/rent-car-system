<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Refunding = 'refunding';
    case Refunded = 'refunded';
    case Fraud = 'fraud';
    case Failed = 'failed';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Completed => 'Completado',
            self::Refunding => 'Reembolsando',
            self::Refunded => 'Reembolsado',
            self::Fraud => 'Fraude',
            self::Failed => 'Fallido',
            self::Canceled => 'Cancelado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Completed => 'success',
            self::Refunding, self::Refunded => 'info',
            self::Fraud, self::Failed, self::Canceled => 'danger',
        };
    }

    public function invoiceStatus(): InvoiceStatus
    {
        return match ($this) {
            self::Completed => InvoiceStatus::Completed,
            self::Pending => InvoiceStatus::Pending,
            default => InvoiceStatus::Canceled,
        };
    }
}
