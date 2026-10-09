<?php

namespace App\Filament\Widgets\Reports\Concerns;

use App\Filament\Clusters\Reservas\Widgets\Concerns\UsesBookingPeriod;
use App\Services\ReportStats;

/**
 * Reads the period chosen in the reports' filters.
 */
trait UsesReportPeriod
{
    use UsesBookingPeriod;

    protected function report(): ReportStats
    {
        return app(ReportStats::class);
    }
}
