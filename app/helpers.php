<?php

use App\Support\Lang;

if (! function_exists('lroute')) {
    /**
     * URL of a public site page (site.home, site.car...) in the language being shown.
     */
    function lroute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        return Lang::route($name, $parameters, $absolute);
    }
}
