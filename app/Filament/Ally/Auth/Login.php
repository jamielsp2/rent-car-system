<?php

namespace App\Filament\Ally\Auth;

use App\Filament\Auth\Login as StaffLogin;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Sign-in for allies: the admin's look and form, with wording for car owners.
 */
class Login extends StaffLogin
{
    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getHeading();
        }

        return 'Hola, aliado';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return parent::getSubheading();
        }

        return 'Revisa cómo rinden tus vehículos y quién los está rentando.';
    }
}
