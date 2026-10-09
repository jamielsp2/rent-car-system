<?php

namespace App\Filament\Clusters\Reservas\Widgets\Concerns;

use App\Services\BookingStats;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

/**
 * Reads the period chosen in the summary's filters.
 */
trait UsesBookingPeriod
{
    use InteractsWithPageFilters;

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function period(): array
    {
        return BookingStats::range($this->pageFilters);
    }

    protected function stats(): BookingStats
    {
        return app(BookingStats::class);
    }
}
