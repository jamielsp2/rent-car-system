<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Dates
{
    public static function date(CarbonInterface|string|null $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    public static function dateTime(CarbonInterface|string|null $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y h:i A') : '';
    }
}
