<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case CashOnDelivery = 'cod';
    case BankTransfer = 'bank_transfer';
    case Stripe = 'stripe';
    case Paypal = 'paypal';

    public function getLabel(): string
    {
        return match ($this) {
            self::CashOnDelivery => 'Pago contra entrega',
            self::BankTransfer => 'Transferencia bancaria',
            self::Stripe => 'Tarjeta (Stripe)',
            self::Paypal => 'PayPal',
        };
    }
}
