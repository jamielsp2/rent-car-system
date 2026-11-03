<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Identity document of a customer. Values are stored in cr_customers.document_type.
 */
enum DocumentType: string implements HasLabel
{
    case Cedula = 'cedula';
    case Passport = 'pasaporte';
    case License = 'licencia';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cedula => 'Cédula',
            self::Passport => 'Pasaporte',
            self::License => 'Licencia',
        };
    }
}
