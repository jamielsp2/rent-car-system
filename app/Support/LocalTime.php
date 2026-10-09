<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Rental pickup/return times are stored as the business's wall-clock time
 * (BUSINESS_TIMEZONE), while system timestamps are UTC. Use these for "today"/"now" next to rentals.
 */
class LocalTime
{
    public static function zone(): string
    {
        return config('app.business_timezone', 'America/Santo_Domingo');
    }

    public static function now(): CarbonImmutable
    {
        // Same wall-clock digits as the rental columns, without a UTC shift on save.
        return CarbonImmutable::parse(CarbonImmutable::now(static::zone())->format('Y-m-d H:i:s'));
    }

    public static function today(): CarbonImmutable
    {
        return static::now()->startOfDay();
    }
}
